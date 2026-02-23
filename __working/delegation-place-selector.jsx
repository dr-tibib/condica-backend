import { useState, useEffect, useRef, useCallback } from "react";

const SAVED_PLACES = [
  {
    id: 1,
    name: "Grand Hotel Marriott",
    address: "Calea Victoriei 63-81, București 010065",
    photo: "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=300&h=200&fit=crop",
    category: "Hotel",
  },
  {
    id: 2,
    name: "Palace of Parliament",
    address: "Strada Izvor 2-4, București 050563",
    photo: "https://images.unsplash.com/photo-1555992336-03a23c7b20ee?w=300&h=200&fit=crop",
    category: "Government",
  },
  {
    id: 3,
    name: "Radisson Blu Hotel",
    address: "Calea Victoriei 63, București 010065",
    photo: "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=300&h=200&fit=crop",
    category: "Hotel",
  },
  {
    id: 4,
    name: "Bucharest Airport Otopeni",
    address: "Calea Bucureștilor 224E, Otopeni 075150",
    photo: "https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=300&h=200&fit=crop",
    category: "Transport",
  },
  {
    id: 5,
    name: "National Arena Stadium",
    address: "Bulevardul Basarabia 37-39, București 030032",
    photo: "https://images.unsplash.com/photo-1577223625816-7546f13df25d?w=300&h=200&fit=crop",
    category: "Venue",
  },
  {
    id: 6,
    name: "Intercontinental Hotel",
    address: "Bulevardul Nicolae Bălcescu 4, București 010044",
    photo: "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=300&h=200&fit=crop",
    category: "Hotel",
  },
];

const SEARCH_RESULTS = [
  {
    id: 101,
    name: "Hotel Cișmigiu",
    address: "Bulevardul Regina Elisabeta 38, București 050017",
    photo: "https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=300&h=200&fit=crop",
    category: "Hotel",
    isNew: true,
  },
  {
    id: 102,
    name: "Central Park Hotel",
    address: "Strada Teodor Mihali 2-4, Cluj-Napoca 400591",
    photo: "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=300&h=200&fit=crop",
    category: "Hotel",
    isNew: true,
  },
  {
    id: 103,
    name: "Athenee Palace Hilton",
    address: "Strada Episcopiei 1-3, București 010292",
    photo: "https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=300&h=200&fit=crop",
    category: "Hotel",
    isNew: true,
  },
];

const KEYBOARD_ROWS = [
  ["Q","W","E","R","T","Y","U","I","O","P"],
  ["A","S","D","F","G","H","J","K","L"],
  ["⇧","Z","X","C","V","B","N","M","⌫"],
  ["123","@","-","SPACE",".",",","✓"],
];

const NUM_ROWS = [
  ["1","2","3"],
  ["4","5","6"],
  ["7","8","9"],
  [".","0","⌫"],
];

export default function DelegationPlaceSelector() {
  const [selected, setSelected] = useState([]);
  const [searchText, setSearchText] = useState("");
  const [isSearching, setIsSearching] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const [focusedCard, setFocusedCard] = useState(0);
  const [showNumpad, setShowNumpad] = useState(false);
  const [shift, setShift] = useState(false);
  const [confirmed, setConfirmed] = useState(false);
  const [activeTab, setActiveTab] = useState("saved"); // saved | results

  const displayPlaces = showResults && searchText.length > 1 ? SEARCH_RESULTS : SAVED_PLACES;

  const toggleSelect = useCallback((place) => {
    setSelected(prev =>
      prev.find(p => p.id === place.id)
        ? prev.filter(p => p.id !== place.id)
        : [...prev, place]
    );
  }, []);

  const isSelected = (id) => selected.some(p => p.id === id);

  const handleKeyPress = (key) => {
    if (key === "⌫") {
      setSearchText(prev => prev.slice(0, -1));
    } else if (key === "SPACE") {
      setSearchText(prev => prev + " ");
    } else if (key === "⇧") {
      setShift(prev => !prev);
    } else if (key === "✓") {
      setShowResults(true);
      setIsSearching(false);
    } else if (key === "123") {
      setShowNumpad(prev => !prev);
    } else if (key === "@" || key === "-" || key === "." || key === ",") {
      setSearchText(prev => prev + key);
    } else {
      const char = shift ? key : key.toLowerCase();
      setSearchText(prev => prev + char);
      if (shift) setShift(false);
    }
  };

  useEffect(() => {
    if (searchText.length > 1) setShowResults(true);
  }, [searchText]);

  useEffect(() => {
    const handleNumpad = (e) => {
      const numpadKeys = { Numpad2: 2, Numpad4: 4, Numpad6: 6, Numpad8: 8 };
      if (e.code === "Numpad5" || e.code === "NumpadEnter") {
        if (!isSearching) toggleSelect(displayPlaces[focusedCard]);
      }
    };
    window.addEventListener("keydown", handleNumpad);
    return () => window.removeEventListener("keydown", handleNumpad);
  }, [focusedCard, displayPlaces, isSearching, toggleSelect]);

  const CategoryBadge = ({ cat }) => {
    const colors = {
      Hotel: "#f59e0b",
      Government: "#6366f1",
      Transport: "#10b981",
      Venue: "#ec4899",
    };
    return (
      <span style={{
        background: colors[cat] || "#64748b",
        color: "#fff",
        fontSize: 10,
        fontWeight: 700,
        letterSpacing: "0.08em",
        padding: "2px 8px",
        borderRadius: 20,
        textTransform: "uppercase",
      }}>{cat}</span>
    );
  };

  const PlaceCard = ({ place, index }) => {
    const sel = isSelected(place.id);
    const focused = focusedCard === index && !isSearching;
    return (
      <div
        onClick={() => toggleSelect(place)}
        onMouseEnter={() => setFocusedCard(index)}
        style={{
          background: sel ? "rgba(251,191,36,0.1)" : "rgba(255,255,255,0.03)",
          border: sel
            ? "2px solid #fbbf24"
            : focused
            ? "2px solid rgba(148,163,184,0.4)"
            : "2px solid rgba(255,255,255,0.06)",
          borderRadius: 12,
          overflow: "hidden",
          cursor: "pointer",
          transition: "all 0.18s ease",
          position: "relative",
          transform: sel ? "scale(1.01)" : "scale(1)",
          boxShadow: sel ? "0 0 20px rgba(251,191,36,0.2)" : focused ? "0 4px 20px rgba(0,0,0,0.3)" : "none",
          display: "flex",
          flexDirection: "column",
        }}
      >
        {/* Selection Overlay Check */}
        {sel && (
          <div style={{
            position: "absolute",
            top: 10, right: 10,
            width: 28, height: 28,
            background: "#fbbf24",
            borderRadius: "50%",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 10,
            boxShadow: "0 2px 8px rgba(0,0,0,0.4)",
            fontSize: 14,
          }}>✓</div>
        )}
        {place.isNew && (
          <div style={{
            position: "absolute",
            top: 10, left: 10,
            background: "#10b981",
            color: "#fff",
            fontSize: 9,
            fontWeight: 800,
            letterSpacing: "0.1em",
            padding: "2px 6px",
            borderRadius: 4,
            zIndex: 10,
          }}>SEARCH RESULT</div>
        )}
        <div style={{ position: "relative", height: 110, overflow: "hidden" }}>
          <img
            src={place.photo}
            alt={place.name}
            style={{
              width: "100%", height: "100%",
              objectFit: "cover",
              filter: sel ? "brightness(0.85)" : "brightness(0.75)",
              transition: "filter 0.2s",
            }}
          />
          <div style={{
            position: "absolute", bottom: 0, left: 0, right: 0,
            height: 40,
            background: "linear-gradient(transparent, rgba(15,23,42,0.9))",
          }} />
        </div>
        <div style={{ padding: "10px 12px 12px" }}>
          <div style={{ marginBottom: 5 }}>
            <CategoryBadge cat={place.category} />
          </div>
          <div style={{
            color: "#f1f5f9",
            fontSize: 13,
            fontWeight: 700,
            fontFamily: "'DM Sans', sans-serif",
            lineHeight: 1.3,
            marginBottom: 4,
          }}>{place.name}</div>
          <div style={{
            color: "#94a3b8",
            fontSize: 11,
            lineHeight: 1.4,
            fontFamily: "'DM Sans', sans-serif",
          }}>{place.address}</div>
        </div>
      </div>
    );
  };

  const KBRow = ({ keys, isNumpad }) => (
    <div style={{ display: "flex", gap: 5, justifyContent: "center", marginBottom: 5 }}>
      {keys.map((k) => {
        const isWide = k === "SPACE";
        const isMed = k === "⌫" || k === "⇧" || k === "✓" || k === "123";
        return (
          <button
            key={k}
            onPointerDown={(e) => { e.preventDefault(); handleKeyPress(k); }}
            style={{
              flex: isWide ? 4 : isMed ? 1.5 : 1,
              height: isNumpad ? 44 : 40,
              background: k === "✓"
                ? "#fbbf24"
                : k === "⌫" || k === "⇧"
                ? "rgba(148,163,184,0.15)"
                : "rgba(255,255,255,0.08)",
              border: "1px solid rgba(255,255,255,0.1)",
              borderRadius: 7,
              color: k === "✓" ? "#0f172a" : "#e2e8f0",
              fontSize: isWide ? 12 : 14,
              fontWeight: k === "✓" ? 800 : 500,
              fontFamily: "'DM Sans', sans-serif",
              cursor: "pointer",
              transition: "background 0.1s",
              letterSpacing: isWide ? "0.08em" : 0,
              userSelect: "none",
            }}
          >
            {k === "⇧" && shift ? "⇑" : k}
          </button>
        );
      })}
    </div>
  );

  const keyboardHeight = 220;
  const mainAreaHeight = isSearching ? 768 - 64 - keyboardHeight : 768 - 64;

  if (confirmed) {
    return (
      <div style={{
        width: 1024, height: 768,
        background: "#0f172a",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        fontFamily: "'DM Sans', sans-serif",
      }}>
        <div style={{ fontSize: 64, marginBottom: 20 }}>✓</div>
        <div style={{ color: "#fbbf24", fontSize: 28, fontWeight: 800, marginBottom: 8 }}>
          Delegation Confirmed
        </div>
        <div style={{ color: "#64748b", fontSize: 16, marginBottom: 40 }}>
          {selected.length} place{selected.length !== 1 ? "s" : ""} selected
        </div>
        {selected.map(p => (
          <div key={p.id} style={{ color: "#94a3b8", fontSize: 14, marginBottom: 4 }}>• {p.name}</div>
        ))}
        <button
          onClick={() => { setConfirmed(false); setSelected([]); setSearchText(""); setShowResults(false); }}
          style={{
            marginTop: 40,
            background: "rgba(255,255,255,0.06)",
            border: "1px solid rgba(255,255,255,0.12)",
            color: "#94a3b8",
            padding: "12px 32px",
            borderRadius: 8,
            cursor: "pointer",
            fontSize: 14,
          }}
        >← Start Over</button>
      </div>
    );
  }

  return (
    <div style={{
      width: 1024,
      height: 768,
      background: "#0c1829",
      display: "flex",
      flexDirection: "column",
      overflow: "hidden",
      fontFamily: "'DM Sans', sans-serif",
      position: "relative",
    }}>
      {/* Google Fonts */}
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap');
        * { box-sizing: border-box; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.2); border-radius: 2px; }
      `}</style>

      {/* Subtle background grid */}
      <div style={{
        position: "absolute", inset: 0,
        backgroundImage: "linear-gradient(rgba(99,102,241,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,0.03) 1px, transparent 1px)",
        backgroundSize: "40px 40px",
        pointerEvents: "none",
      }} />

      {/* === HEADER === */}
      <div style={{
        height: 64,
        background: "rgba(15,23,42,0.95)",
        borderBottom: "1px solid rgba(255,255,255,0.07)",
        display: "flex",
        alignItems: "center",
        padding: "0 24px",
        gap: 16,
        flexShrink: 0,
        backdropFilter: "blur(10px)",
        position: "relative",
        zIndex: 20,
      }}>
        {/* Logo / Title */}
        <div style={{
          display: "flex",
          alignItems: "center",
          gap: 10,
          marginRight: 8,
        }}>
          <div style={{
            width: 36, height: 36,
            background: "linear-gradient(135deg, #fbbf24, #f59e0b)",
            borderRadius: 8,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            fontSize: 18,
            flexShrink: 0,
          }}>📍</div>
          <div>
            <div style={{ color: "#f1f5f9", fontSize: 15, fontWeight: 800, letterSpacing: "-0.01em", lineHeight: 1 }}>
              Place Selector
            </div>
            <div style={{ color: "#475569", fontSize: 10, fontWeight: 500, letterSpacing: "0.08em", textTransform: "uppercase" }}>
              Delegation Management
            </div>
          </div>
        </div>

        {/* Search Bar */}
        <div
          onClick={() => setIsSearching(true)}
          style={{
            flex: 1,
            height: 40,
            background: isSearching ? "rgba(251,191,36,0.05)" : "rgba(255,255,255,0.05)",
            border: isSearching ? "1.5px solid rgba(251,191,36,0.5)" : "1.5px solid rgba(255,255,255,0.1)",
            borderRadius: 8,
            display: "flex",
            alignItems: "center",
            padding: "0 14px",
            gap: 10,
            cursor: "pointer",
            transition: "all 0.2s",
          }}
        >
          <span style={{ color: "#475569", fontSize: 16 }}>🔍</span>
          <span style={{
            color: searchText ? "#f1f5f9" : "#475569",
            fontSize: 14,
            fontFamily: "'DM Sans', sans-serif",
            flex: 1,
          }}>
            {searchText || "Search for a new place..."}
            {isSearching && <span style={{ 
              opacity: 1, 
              animation: "blink 1s step-end infinite",
              color: "#fbbf24",
            }}>|</span>}
          </span>
          {searchText && (
            <span
              onClick={(e) => { e.stopPropagation(); setSearchText(""); setShowResults(false); setIsSearching(false); }}
              style={{ color: "#475569", fontSize: 14, cursor: "pointer", padding: "2px 6px" }}
            >✕</span>
          )}
          <span style={{ color: "#334155", fontSize: 11, fontFamily: "'Space Mono', monospace" }}>
            {isSearching ? "ESC" : "⌨"}
          </span>
        </div>

        {/* Selected count pill */}
        <div style={{
          display: "flex",
          alignItems: "center",
          gap: 8,
          background: selected.length ? "rgba(251,191,36,0.1)" : "rgba(255,255,255,0.04)",
          border: selected.length ? "1px solid rgba(251,191,36,0.3)" : "1px solid rgba(255,255,255,0.06)",
          borderRadius: 8,
          padding: "6px 14px",
          flexShrink: 0,
        }}>
          <span style={{ fontSize: 16 }}>📌</span>
          <div>
            <div style={{ color: selected.length ? "#fbbf24" : "#475569", fontSize: 18, fontWeight: 800, lineHeight: 1, fontFamily: "'Space Mono', monospace" }}>
              {selected.length}
            </div>
            <div style={{ color: "#334155", fontSize: 9, letterSpacing: "0.06em", textTransform: "uppercase" }}>selected</div>
          </div>
        </div>

        {/* Confirm Button */}
        <button
          onClick={() => selected.length && setConfirmed(true)}
          style={{
            height: 40,
            padding: "0 20px",
            background: selected.length ? "linear-gradient(135deg, #fbbf24, #f59e0b)" : "rgba(255,255,255,0.04)",
            border: "none",
            borderRadius: 8,
            color: selected.length ? "#0f172a" : "#334155",
            fontSize: 13,
            fontWeight: 800,
            fontFamily: "'DM Sans', sans-serif",
            cursor: selected.length ? "pointer" : "not-allowed",
            transition: "all 0.2s",
            letterSpacing: "0.02em",
            flexShrink: 0,
          }}
        >
          CONFIRM →
        </button>
      </div>

      {/* === MAIN CONTENT AREA === */}
      <div style={{
        flex: 1,
        display: "flex",
        flexDirection: "column",
        overflow: "hidden",
        transition: "height 0.3s ease",
        height: mainAreaHeight,
      }}>
        {/* Tabs + Filter Row */}
        <div style={{
          display: "flex",
          alignItems: "center",
          padding: "12px 24px 0",
          gap: 6,
          flexShrink: 0,
        }}>
          <button
            onClick={() => { setShowResults(false); setActiveTab("saved"); }}
            style={{
              padding: "7px 16px",
              background: !showResults ? "rgba(251,191,36,0.12)" : "transparent",
              border: !showResults ? "1px solid rgba(251,191,36,0.3)" : "1px solid transparent",
              borderRadius: 20,
              color: !showResults ? "#fbbf24" : "#475569",
              fontSize: 12,
              fontWeight: 700,
              fontFamily: "'DM Sans', sans-serif",
              cursor: "pointer",
              letterSpacing: "0.04em",
            }}
          >SAVED PLACES ({SAVED_PLACES.length})</button>
          {showResults && (
            <button
              onClick={() => setActiveTab("results")}
              style={{
                padding: "7px 16px",
                background: "rgba(16,185,129,0.1)",
                border: "1px solid rgba(16,185,129,0.3)",
                borderRadius: 20,
                color: "#10b981",
                fontSize: 12,
                fontWeight: 700,
                fontFamily: "'DM Sans', sans-serif",
                cursor: "pointer",
                letterSpacing: "0.04em",
                display: "flex",
                alignItems: "center",
                gap: 6,
              }}
            >
              <span style={{ width: 6, height: 6, background: "#10b981", borderRadius: "50%", display: "inline-block" }} />
              SEARCH RESULTS ({SEARCH_RESULTS.length})
            </button>
          )}
          <div style={{ flex: 1 }} />
          {selected.length > 0 && (
            <button
              onClick={() => setSelected([])}
              style={{
                padding: "7px 12px",
                background: "transparent",
                border: "1px solid rgba(239,68,68,0.3)",
                borderRadius: 20,
                color: "#ef4444",
                fontSize: 11,
                fontWeight: 600,
                cursor: "pointer",
                fontFamily: "'DM Sans', sans-serif",
              }}
            >✕ Clear All</button>
          )}
          <div style={{ color: "#1e3a5f", fontSize: 11, fontFamily: "'Space Mono', monospace", marginLeft: 8 }}>
            NUMPAD: 2/4/6/8=NAV · 5=SEL · 0=CONFIRM
          </div>
        </div>

        {/* Cards Grid */}
        <div style={{
          flex: 1,
          overflow: "auto",
          padding: "12px 24px",
        }}>
          <div style={{
            display: "grid",
            gridTemplateColumns: "repeat(4, 1fr)",
            gap: 12,
          }}>
            {displayPlaces.map((place, i) => (
              <PlaceCard key={place.id} place={place} index={i} />
            ))}
          </div>
        </div>

        {/* Selected Tray */}
        {selected.length > 0 && (
          <div style={{
            flexShrink: 0,
            padding: "10px 24px",
            borderTop: "1px solid rgba(255,255,255,0.06)",
            background: "rgba(15,23,42,0.8)",
            display: "flex",
            alignItems: "center",
            gap: 10,
            overflowX: "auto",
          }}>
            <span style={{ color: "#334155", fontSize: 10, fontWeight: 700, letterSpacing: "0.1em", textTransform: "uppercase", flexShrink: 0 }}>Selected:</span>
            {selected.map(p => (
              <div key={p.id} style={{
                display: "flex",
                alignItems: "center",
                gap: 8,
                background: "rgba(251,191,36,0.08)",
                border: "1px solid rgba(251,191,36,0.25)",
                borderRadius: 20,
                padding: "4px 8px 4px 4px",
                flexShrink: 0,
              }}>
                <img src={p.photo} alt="" style={{ width: 22, height: 22, borderRadius: "50%", objectFit: "cover" }} />
                <span style={{ color: "#fbbf24", fontSize: 12, fontWeight: 600 }}>{p.name}</span>
                <span
                  onClick={() => toggleSelect(p)}
                  style={{ color: "#64748b", fontSize: 12, cursor: "pointer", lineHeight: 1 }}
                >✕</span>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* === ON-SCREEN KEYBOARD === */}
      {isSearching && (
        <div style={{
          height: keyboardHeight,
          background: "rgba(10,18,32,0.98)",
          borderTop: "1px solid rgba(255,255,255,0.08)",
          padding: "12px 24px 10px",
          flexShrink: 0,
          backdropFilter: "blur(20px)",
        }}>
          <div style={{ display: "flex", gap: 12 }}>
            {/* QWERTY / Alpha keyboard */}
            <div style={{ flex: 1 }}>
              {!showNumpad ? (
                KEYBOARD_ROWS.map((row, ri) => (
                  <KBRow key={ri} keys={row} />
                ))
              ) : (
                <div style={{ display: "flex", flexDirection: "column", alignItems: "center", marginTop: 8 }}>
                  {NUM_ROWS.map((row, ri) => (
                    <div key={ri} style={{ display: "flex", gap: 5, marginBottom: 5 }}>
                      {row.map(k => (
                        <button
                          key={k}
                          onPointerDown={(e) => { e.preventDefault(); handleKeyPress(k); }}
                          style={{
                            width: 60, height: 44,
                            background: k === "⌫" ? "rgba(148,163,184,0.15)" : "rgba(255,255,255,0.08)",
                            border: "1px solid rgba(255,255,255,0.1)",
                            borderRadius: 7,
                            color: "#e2e8f0",
                            fontSize: 16,
                            fontWeight: 600,
                            cursor: "pointer",
                            fontFamily: "'Space Mono', monospace",
                          }}
                        >{k}</button>
                      ))}
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Action panel */}
            <div style={{ width: 160, display: "flex", flexDirection: "column", gap: 6 }}>
              <button
                onPointerDown={(e) => { e.preventDefault(); setShowNumpad(p=>!p); }}
                style={{
                  height: 40,
                  background: showNumpad ? "rgba(251,191,36,0.1)" : "rgba(255,255,255,0.06)",
                  border: showNumpad ? "1px solid rgba(251,191,36,0.4)" : "1px solid rgba(255,255,255,0.1)",
                  borderRadius: 7,
                  color: showNumpad ? "#fbbf24" : "#64748b",
                  fontSize: 12,
                  fontWeight: 700,
                  cursor: "pointer",
                  fontFamily: "'DM Sans', sans-serif",
                  letterSpacing: "0.06em",
                }}
              >{showNumpad ? "◀ ALPHA" : "123 NUMPAD"}</button>

              <button
                onPointerDown={(e) => { e.preventDefault(); setSearchText(""); }}
                style={{
                  height: 40,
                  background: "rgba(255,255,255,0.04)",
                  border: "1px solid rgba(255,255,255,0.08)",
                  borderRadius: 7,
                  color: "#475569",
                  fontSize: 12,
                  fontWeight: 600,
                  cursor: "pointer",
                  fontFamily: "'DM Sans', sans-serif",
                }}
              >✕ Clear</button>

              <div style={{ flex: 1 }} />

              <button
                onPointerDown={(e) => {
                  e.preventDefault();
                  setShowResults(searchText.length > 0);
                  setIsSearching(false);
                }}
                style={{
                  height: 56,
                  background: "linear-gradient(135deg, #fbbf24, #f59e0b)",
                  border: "none",
                  borderRadius: 9,
                  color: "#0f172a",
                  fontSize: 13,
                  fontWeight: 800,
                  cursor: "pointer",
                  fontFamily: "'DM Sans', sans-serif",
                  letterSpacing: "0.04em",
                }}
              >🔍 SEARCH</button>

              <button
                onPointerDown={(e) => { e.preventDefault(); setIsSearching(false); }}
                style={{
                  height: 36,
                  background: "transparent",
                  border: "1px solid rgba(255,255,255,0.08)",
                  borderRadius: 7,
                  color: "#334155",
                  fontSize: 12,
                  cursor: "pointer",
                  fontFamily: "'DM Sans', sans-serif",
                }}
              >ESC ↓</button>
            </div>
          </div>
        </div>
      )}

      {/* Blink CSS */}
      <style>{`
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
      `}</style>
    </div>
  );
}
