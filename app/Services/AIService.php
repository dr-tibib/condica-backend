<?php

declare(strict_types=1);

namespace App\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Tool;

class AIService
{
    private string $provider;

    private string $model;

    public function __construct()
    {
        $this->provider = config('ai.provider', 'gemini');
        $this->model = config('ai.model', 'gemini-2.0-flash');
    }

    /**
     * Generate a daily briefing summary for a manager about their team's attendance.
     *
     * @param  array{
     *     present: array<int, string>,
     *     absent: array<int, string>,
     *     on_leave: array<int, string>,
     *     on_delegation: array<int, string>,
     *     unclosed_yesterday: array<int, string>,
     * }  $teamData
     */
    public function generateBriefing(array $teamData, string $managerName, string $date): string
    {
        $prompt = $this->buildBriefingPrompt($teamData, $managerName, $date);

        $response = Prism::text()
            ->using(Provider::from($this->provider), $this->model)
            ->withPrompt($prompt)
            ->asText();

        return $response->text;
    }

    /**
     * Generate an AI narrative summary for the Condica monthly report.
     *
     * @param  array{
     *     v: array<int, array<string, mixed>>,
     *     k: array{month: string, year: int},
     *     companyName: string,
     * }  $reportData
     */
    public function generateCondicaNarrative(array $reportData, int $month, int $year): string
    {
        $prompt = $this->buildCondicaNarrativePrompt($reportData, $month, $year);

        $response = Prism::text()
            ->using(Provider::from($this->provider), $this->model)
            ->withPrompt($prompt)
            ->asText();

        return $response->text;
    }

    /**
     * Generate AI insights for the admin dashboard using Prism tool-calling.
     *
     * @param  Tool[]  $tools
     */
    public function generateAdminInsights(
        array $tools,
        string $month,
        string $year,
        string $companyName
    ): string {
        $response = Prism::text()
            ->using(Provider::from($this->provider), $this->model)
            ->withSystemPrompt(
                'Ești un analist HR pentru sisteme de management al prezenței din România. '.
                'Analizează datele din instrumente și generează maxim 5 concluzii clare, acționabile, în română. '.
                'IMPORTANT: Folosește ÎNTOTDEAUNA numele exacte ale angajaților din datele furnizate, nu referințe vagi precum "2 angajați" sau "un angajat". '.
                'Menționează ore reale, date specifice și valori concrete din date. '.
                'Evidențiază anomalii și riscuri de conformitate cu legislația muncii din România (Codul Muncii).'
            )
            ->withPrompt("Analizează prezența angajaților pentru {$month} {$year} la {$companyName}.")
            ->withTools($tools)
            ->withMaxSteps(4)
            ->asText();

        return $response->text;
    }

    /**
     * @param  array{
     *     present: array<int, string>,
     *     absent: array<int, string>,
     *     on_leave: array<int, string>,
     *     on_delegation: array<int, string>,
     *     unclosed_yesterday: array<int, string>,
     * }  $teamData
     */
    private function buildBriefingPrompt(array $teamData, string $managerName, string $date): string
    {
        $present = implode(', ', $teamData['present']) ?: 'niciunul';
        $absent = implode(', ', $teamData['absent']) ?: 'niciunul';
        $onLeave = implode(', ', $teamData['on_leave']) ?: 'niciunul';
        $onDelegation = implode(', ', $teamData['on_delegation']) ?: 'niciunul';
        $unclosed = implode(', ', $teamData['unclosed_yesterday']) ?: 'niciunul';

        return <<<PROMPT
        Ești un asistent HR pentru o companie românească. Generează un rezumat zilnic de prezență pentru managerul {$managerName} pe data de {$date}.

        Date echipă:
        - Prezenți la birou: {$present}
        - Absenți (fără pontaj): {$absent}
        - În concediu: {$onLeave}
        - În delegație: {$onDelegation}
        - Pontaje neînchise din ziua precedentă (erori): {$unclosed}

        Scrie un rezumat prietenos și profesionist în limba română, de 3-5 propoziții. Evidențiază orice aspecte care necesită atenție (absențe nemotivate, erori de pontaj). Fii direct și concis.
        PROMPT;
    }

    /**
     * @param  array{
     *     v: array<int, array<string, mixed>>,
     *     k: array{month: string, year: int},
     *     companyName: string,
     * }  $reportData
     */
    private function buildCondicaNarrativePrompt(array $reportData, int $month, int $year): string
    {
        $employees = $reportData['v'];
        $monthName = $reportData['k']['month'];
        $companyName = $reportData['companyName'];

        $totalEmployees = count($employees);
        $totalHoursWorked = array_sum(array_column($employees, 'worked'));
        $avgHours = $totalEmployees > 0 ? round($totalHoursWorked / $totalEmployees, 1) : 0;

        $overtimeEmployees = array_filter($employees, fn ($e) => ($e['worked'] ?? 0) > 160);
        $overtimeNames = array_map(fn ($e) => $e['name'], $overtimeEmployees);

        $sickLeaveTotal = array_sum(array_column($employees, 'cm'));
        $annualLeaveTotal = array_sum(array_column($employees, 'co'));
        $delegationDaysTotal = 0;

        $stats = [
            'total_employees' => $totalEmployees,
            'total_hours' => $totalHoursWorked,
            'avg_hours' => $avgHours,
            'overtime_employees' => implode(', ', $overtimeNames) ?: 'niciunul',
            'sick_leave_days' => $sickLeaveTotal,
            'annual_leave_days' => $annualLeaveTotal,
        ];

        return <<<PROMPT
        Ești un analist HR pentru compania "{$companyName}". Generează un sumar executiv al condicii de prezență pentru luna {$monthName} {$year}.

        Statistici lunare:
        - Total angajați: {$stats['total_employees']}
        - Total ore lucrate: {$stats['total_hours']}
        - Medie ore/angajat: {$stats['avg_hours']}
        - Angajați cu ore suplimentare (>160h): {$stats['overtime_employees']}
        - Total zile concediu medical (CM): {$stats['sick_leave_days']}
        - Total zile concediu de odihnă (CO): {$stats['annual_leave_days']}

        Scrie un sumar profesionist în limba română care include:
        1. Prezentare generală a prezenței
        2. Anomalii și aspecte care necesită atenție (ore suplimentare excesive, concedii medicale frecvente)
        3. Conformitate cu legislația muncii din România (Codul Muncii)
        4. Recomandări dacă există probleme

        Structurează răspunsul cu titluri clare. Fii concis și orientat pe acțiune.
        PROMPT;
    }
}
