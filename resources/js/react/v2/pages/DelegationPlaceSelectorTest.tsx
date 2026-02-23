import { useState, useEffect, useCallback, useRef, useMemo } from "react";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import { APIProvider, useMapsLibrary } from '@vis.gl/react-google-maps';

interface Place {
  id?: number;
  google_place_id: string;
  name: string;
  address?: string;
  photo_reference?: string;
  latitude?: number;
  longitude?: number;
  category?: string;
  isNew?: boolean;
}

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

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

function PlaceSelectorContent() {
  const navigate = useNavigate();
  const placesLibrary = useMapsLibrary('places');
  const [savedPlaces, setSavedPlaces] = useState<Place[]>([]);
  const [googleResults, setGoogleResults] = useState<Place[]>([]);
  const [selected, setSelected] = useState<Place[]>([]);
  const [searchText, setSearchText] = useState("");
  const [isSearching, setIsSearching] = useState(false);
  const [focusedCard, setFocusedCard] = useState(0);
  const [showNumpad, setShowNumpad] = useState(false);
  const [shift, setShift] = useState(false);
  const [activeTab, setActiveTab] = useState<"saved" | "results">("saved");
  const [isLoading, setIsLoading] = useState(false);
  const [confirmed, setConfirmed] = useState(false);

  const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    fetchSavedPlaces();
  }, []);

  const fetchSavedPlaces = async () => {
    try {
      const response = await axios.get("/api/kiosk/saved-places");
      if (response.data && response.data.data) {
        setSavedPlaces(response.data.data);
      }
    } catch (error) {
      console.error("Failed to fetch places", error);
    }
  };

  const performSearch = async (query: string) => {
    if (!placesLibrary || query.length < 2) {
      setGoogleResults([]);
      return;
    }
    
    setIsLoading(true);
    try {
      // @ts-ignore
      const response = await placesLibrary.AutocompleteSuggestion.fetchAutocompleteSuggestions({ input: query });
      const suggestions = response.suggestions || [];
      
      const mappedResults: Place[] = suggestions.map((s: any) => ({
        google_place_id: s.placePrediction.placeId,
        name: s.placePrediction.mainText.text,
        address: s.placePrediction.secondaryText?.text || "",
        isNew: true
      }));
      
      setGoogleResults(mappedResults);
    } catch (error) {
      console.error("Google Search failed", error);
      setGoogleResults([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
    
    if (searchText.length > 0) {
        setActiveTab("results");
    } else {
        setActiveTab("saved");
    }

    if (searchText.length > 2) {
      searchTimeoutRef.current = setTimeout(() => {
        performSearch(searchText);
      }, 500);
    } else {
      setGoogleResults([]);
    }
    
    return () => {
      if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
    };
  }, [searchText, placesLibrary]);

  const matchingSaved = useMemo(() => {
    if (!searchText) return [];
    const lower = searchText.toLowerCase();
    return savedPlaces.filter(p => 
        p.name.toLowerCase().includes(lower) || 
        (p.address && p.address.toLowerCase().includes(lower))
    );
  }, [savedPlaces, searchText]);

  const displayPlaces = useMemo(() => {
    if (activeTab === "saved") return savedPlaces;
    
    // Prioritize saved places first, then google results
    const results = [...matchingSaved];
    googleResults.forEach(gr => {
        if (!results.find(r => r.google_place_id === gr.google_place_id)) {
            results.push(gr);
        }
    });
    return results;
  }, [activeTab, savedPlaces, matchingSaved, googleResults]);

  const toggleSelect = useCallback((place: Place) => {
    if (!place) return;
    setSelected(prev =>
      prev.find(p => p.google_place_id === place.google_place_id)
        ? prev.filter(p => p.google_place_id !== place.google_place_id)
        : [...prev, place]
    );
  }, []);

  const isSelected = (google_place_id: string) => selected.some(p => p.google_place_id === google_place_id);

  const handleKeyPress = (key: string) => {
    if (key === "⌫") {
      setSearchText(prev => prev.slice(0, -1));
    } else if (key === "SPACE") {
      setSearchText(prev => prev + " ");
    } else if (key === "⇧") {
      setShift(prev => !prev);
    } else if (key === "✓") {
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
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.code === "Numpad5" || e.code === "Enter") {
        if (!isSearching && displayPlaces[focusedCard]) {
            toggleSelect(displayPlaces[focusedCard]);
        }
      }
      if (e.code === "Numpad8" || e.code === "ArrowUp") {
          setFocusedCard(prev => Math.max(0, prev - 4));
      }
      if (e.code === "Numpad2" || e.code === "ArrowDown") {
          setFocusedCard(prev => Math.min(displayPlaces.length - 1, prev + 4));
      }
      if (e.code === "Numpad4" || e.code === "ArrowLeft") {
          setFocusedCard(prev => Math.max(0, prev - 1));
      }
      if (e.code === "Numpad6" || e.code === "ArrowRight") {
          setFocusedCard(prev => Math.min(displayPlaces.length - 1, prev + 1));
      }
      if (e.code === "Numpad0") {
          if (selected.length > 0) handleConfirm();
      }
      if (e.key === "Escape") {
          setIsSearching(false);
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [focusedCard, displayPlaces, isSearching, toggleSelect, selected]);

  const handleConfirm = () => {
      setConfirmed(true);
  };

  const getPhotoUrl = (photoRef?: string) => {
      if (!photoRef) return "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=300&h=200&fit=crop";
      if (photoRef.startsWith('http')) return photoRef;
      if (photoRef.startsWith('places/')) {
          return `https://places.googleapis.com/v1/${photoRef}/media?maxHeightPx=400&maxWidthPx=400&key=${GOOGLE_MAPS_API_KEY}`;
      }
      return `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photo_reference=${photoRef}&key=${GOOGLE_MAPS_API_KEY}`;
  };

  const CategoryBadge = ({ cat }: { cat?: string }) => {
    if (!cat) return null;
    const colors: Record<string, string> = {
      Hotel: "bg-amber-500",
      Government: "bg-indigo-500",
      Transport: "bg-emerald-500",
      Venue: "bg-pink-500",
    };
    return (
      <span className={`${colors[cat] || "bg-slate-500"} text-white text-[10px] font-bold tracking-widest px-2 py-0.5 rounded-full uppercase`}>
        {cat}
      </span>
    );
  };

  const PlaceCard = ({ place, index }: { place: Place, index: number }) => {
    const sel = isSelected(place.google_place_id);
    const focused = focusedCard === index && !isSearching;
    const isSaved = savedPlaces.some(p => p.google_place_id === place.google_place_id);

    return (
      <div
        onClick={() => toggleSelect(place)}
        onMouseEnter={() => setFocusedCard(index)}
        className={`relative flex flex-col h-[220px] rounded-2xl overflow-hidden cursor-pointer transition-all duration-200 border-2 ${
            sel 
            ? "border-primary bg-blue-50 dark:bg-blue-900/20 shadow-lg shadow-primary/10" 
            : focused
            ? "border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800 shadow-xl"
            : "border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800"
        } ${sel ? "scale-[1.02]" : "scale-100"}`}
      >
        {sel && (
          <div className="absolute top-3 right-3 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center z-10 shadow-md">
            <span className="material-symbols-outlined text-xl">check</span>
          </div>
        )}
        
        <div className="absolute top-3 left-3 flex flex-col gap-1 z-10">
            {isSaved && (
                <div className="bg-amber-500 text-white text-[9px] font-black tracking-tighter px-2 py-0.5 rounded shadow-sm flex items-center gap-1 uppercase">
                    <span className="material-symbols-outlined text-[12px]">star</span>
                    SALVAT
                </div>
            )}
            {place.isNew && !isSaved && (
                <div className="bg-emerald-500 text-white text-[9px] font-black tracking-tighter px-2 py-0.5 rounded shadow-sm flex items-center gap-1 uppercase">
                    <span className="material-symbols-outlined text-[12px]">search</span>
                    GOOGLE
                </div>
            )}
        </div>

        <div className="relative h-[120px] overflow-hidden bg-slate-200 dark:bg-slate-700">
          <img
            src={getPhotoUrl(place.photo_reference)}
            alt={place.name}
            className={`w-full h-full object-cover transition-all duration-300 ${sel ? "brightness-90" : "brightness-75 group-hover:brightness-90"}`}
            onError={(e) => {
                (e.target as HTMLImageElement).src = "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=300&h=200&fit=crop";
            }}
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
        </div>
        
        <div className="p-3 flex flex-col flex-grow">
          <div className="mb-1">
            <CategoryBadge cat={place.category} />
          </div>
          <h3 className={`text-sm font-bold leading-tight line-clamp-2 mb-1 ${sel ? "text-primary" : "text-slate-800 dark:text-slate-100"}`}>
            {place.name}
          </h3>
          <p className="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
            {place.address}
          </p>
        </div>
      </div>
    );
  };

  if (confirmed) {
    return (
      <div className="w-[1024px] h-[768px] bg-slate-50 dark:bg-slate-900 flex flex-col items-center justify-center font-sans">
        <div className="w-24 h-24 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-full flex items-center justify-center mb-8 shadow-xl">
            <span className="material-symbols-outlined text-6xl">check_circle</span>
        </div>
        <h1 className="text-slate-800 dark:text-white text-4xl font-black mb-2 uppercase tracking-tight">Delegație Confirmată</h1>
        <p className="text-slate-500 dark:text-slate-400 text-xl mb-12">
          {selected.length} locați{selected.length !== 1 ? "i" : "e"} selectat{selected.length !== 1 ? "e" : "ă"}
        </p>
        
        <div className="flex flex-col gap-3 w-full max-w-md mb-12">
            {selected.map(p => (
              <div key={p.google_place_id} className="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-4 shadow-sm">
                  <img src={getPhotoUrl(p.photo_reference)} className="w-12 h-12 rounded-lg object-cover" />
                  <div className="min-w-0">
                      <div className="font-bold text-slate-800 dark:text-slate-100 truncate">{p.name}</div>
                      <div className="text-xs text-slate-500 truncate">{p.address}</div>
                  </div>
              </div>
            ))}
        </div>

        <button
          onClick={() => { setConfirmed(false); setSelected([]); setSearchText(""); setShowNumpad(false); }}
          className="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-8 py-4 rounded-2xl font-bold hover:bg-slate-300 transition-colors uppercase tracking-widest flex items-center gap-2"
        >
          <span className="material-symbols-outlined">refresh</span>
          Reia Procesul
        </button>
      </div>
    );
  }

  return (
    <div className="w-[1024px] h-[768px] bg-slate-50 dark:bg-slate-900 flex flex-col overflow-hidden font-sans relative">
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.2); border-radius: 2px; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
      `}</style>

      {/* === HEADER === */}
      <header className="h-20 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center px-8 gap-6 shrink-0 z-20 shadow-sm">
        <div className="flex items-center gap-3 mr-4 cursor-pointer" onClick={() => navigate('/')}>
          <div className="w-12 h-12 bg-primary text-white rounded-xl flex items-center justify-center text-2xl shadow-lg shadow-primary/20">
            <span className="material-symbols-outlined">location_on</span>
          </div>
          <div>
            <div className="text-slate-800 dark:text-white text-lg font-black leading-none uppercase tracking-tight">
              Selector Locații
            </div>
            <div className="text-slate-400 text-[10px] font-bold tracking-widest uppercase mt-1">
              Configurare Delegație
            </div>
          </div>
        </div>

        <div
          onClick={() => setIsSearching(true)}
          className={`flex-1 h-14 rounded-2xl flex items-center px-5 gap-4 cursor-pointer transition-all duration-300 border-2 ${
              isSearching 
              ? "bg-blue-50 dark:bg-blue-900/10 border-primary" 
              : "bg-slate-100 dark:bg-slate-700/50 border-transparent hover:border-slate-200"
          }`}
        >
          <span className={`material-symbols-outlined ${isSearching ? "text-primary" : "text-slate-400"}`}>search</span>
          <span className={`flex-1 text-lg font-medium ${searchText ? "text-slate-800 dark:text-slate-100" : "text-slate-400"}`}>
            {searchText || "Caută o firmă sau un oraș..."}
            {isSearching && <span className="text-primary animate-[blink_1s_step-end_infinite]">|</span>}
          </span>
          {searchText && (
            <button
              onClick={(e) => { e.stopPropagation(); setSearchText(""); setIsSearching(false); }}
              className="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
            >
              <span className="material-symbols-outlined">close</span>
            </button>
          )}
        </div>

        <div className="flex items-center gap-4 bg-slate-100 dark:bg-slate-700/50 px-5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-600">
           <div className="text-right">
              <div className="text-primary text-2xl font-black leading-none">{selected.length}</div>
              <div className="text-slate-400 text-[9px] font-bold tracking-widest uppercase">Selectate</div>
           </div>
           <span className="material-symbols-outlined text-slate-300">push_pin</span>
        </div>

        <button
          onClick={() => selected.length && handleConfirm()}
          disabled={!selected.length}
          className={`h-14 px-8 rounded-2xl font-black text-lg transition-all shadow-xl flex items-center gap-3 uppercase tracking-tight ${
              selected.length 
              ? "bg-green-600 text-white shadow-green-500/20 hover:bg-green-700 active:scale-[0.98]" 
              : "bg-slate-200 dark:bg-slate-700 text-slate-400 cursor-not-allowed"
          }`}
        >
          Confirmă
          <span className="material-symbols-outlined">arrow_forward</span>
        </button>
      </header>

      {/* === MAIN CONTENT === */}
      <main className="flex-1 flex flex-col overflow-hidden">
        {/* Tabs Bar */}
        <div className="flex items-center px-8 py-5 gap-3 shrink-0">
          <button
            onClick={() => { setActiveTab("saved"); setSearchText(""); }}
            className={`px-6 py-2.5 rounded-full text-xs font-bold tracking-widest uppercase transition-all flex items-center gap-2 ${
                activeTab === "saved" 
                ? "bg-primary text-white shadow-lg shadow-primary/20" 
                : "bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700"
            }`}
          >
            <span className="material-symbols-outlined text-lg">star</span>
            Locații Recurente ({savedPlaces.length})
          </button>
          
          {(activeTab === "results" || searchText) && (
            <button
              onClick={() => setActiveTab("results")}
              className={`px-6 py-2.5 rounded-full text-xs font-bold tracking-widest uppercase transition-all flex items-center gap-2 ${
                  activeTab === "results" 
                  ? "bg-emerald-600 text-white shadow-lg shadow-emerald-500/20" 
                  : "bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700"
              }`}
            >
              <span className="material-symbols-outlined text-lg">search</span>
              Rezultate Căutare ({displayPlaces.length})
            </button>
          )}

          <div className="flex-1" />
          
          {isLoading && (
              <div className="flex items-center gap-2 text-primary">
                  <div className="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin" />
                  <span className="text-[10px] font-bold uppercase tracking-widest">Căutare...</span>
              </div>
          )}

          {selected.length > 0 && (
            <button
              onClick={() => setSelected([])}
              className="text-red-500 text-[10px] font-bold tracking-widest uppercase hover:underline flex items-center gap-1"
            >
              <span className="material-symbols-outlined text-sm">delete_sweep</span>
              Șterge Tot
            </button>
          )}

          <div className="flex items-center gap-3 ml-6 py-2 px-4 bg-slate-200/50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
             <div className="flex items-center gap-2">
                 <span className="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-slate-700 rounded-lg text-[10px] font-black text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 shadow-sm">
                    <span className="text-primary">8/2/4/6</span> Navigare
                 </span>
                 <span className="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-slate-700 rounded-lg text-[10px] font-black text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 shadow-sm">
                    <span className="text-primary">5</span> Select
                 </span>
                 <span className="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-slate-700 rounded-lg text-[10px] font-black text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 shadow-sm">
                    <span className="text-primary">0</span> Confirm
                 </span>
             </div>
          </div>
        </div>

        {/* Grid Area */}
        <div className="flex-1 overflow-y-auto px-8 pb-8">
          <div className="grid grid-cols-4 gap-4">
            {displayPlaces.map((place, i) => (
              <PlaceCard key={`${place.google_place_id}-${i}`} place={place} index={i} />
            ))}
          </div>
          
          {displayPlaces.length === 0 && !isLoading && (
              <div className="flex flex-col items-center justify-center py-24 text-slate-400">
                  <span className="material-symbols-outlined text-6xl opacity-20 mb-4">location_off</span>
                  <p className="text-lg font-medium">
                      {activeTab === "results" ? "Nu am găsit nicio locație pentru această căutare." : "Nu ai nicio locație salvată."}
                  </p>
              </div>
          )}
        </div>

        {/* Selected Tray */}
        {selected.length > 0 && (
          <div className="h-20 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex items-center px-8 gap-4 overflow-x-auto shrink-0 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
            <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0">Locații Selectate:</span>
            <div className="flex items-center gap-3">
                {selected.map(p => (
                  <div key={p.google_place_id} className="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 border border-primary/30 rounded-full pl-1.5 pr-3 py-1.5 shrink-0 transition-all hover:border-primary">
                    <img src={getPhotoUrl(p.photo_reference)} className="w-7 h-7 rounded-full object-cover shadow-sm" />
                    <span className="text-xs font-bold text-primary truncate max-w-[120px]">{p.name}</span>
                    <button
                      onClick={() => toggleSelect(p)}
                      className="w-5 h-5 flex items-center justify-center text-primary/50 hover:text-primary transition-colors"
                    >
                      <span className="material-symbols-outlined text-base">close</span>
                    </button>
                  </div>
                ))}
            </div>
          </div>
        )}
      </main>

      {/* === VIRTUAL KEYBOARD === */}
      {isSearching && (
        <div className="absolute bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur-xl border-t border-white/10 p-6 z-50 animate-slide-up">
          <div className="max-w-5xl mx-auto flex gap-8">
            <div className="flex-1">
              {!showNumpad ? (
                KEYBOARD_ROWS.map((row, ri) => (
                  <div key={ri} className="flex gap-2 justify-center mb-2">
                      {row.map((k) => {
                        const isWide = k === "SPACE";
                        const isMed = k === "⌫" || k === "⇧" || k === "✓" || k === "123";
                        return (
                          <button
                            key={k}
                            onPointerDown={(e) => { e.preventDefault(); handleKeyPress(k); }}
                            className={`h-14 rounded-xl text-lg font-bold transition-all border shadow-sm flex items-center justify-center uppercase select-none active:scale-95 ${
                                k === "✓"
                                ? "flex-[1.5] bg-green-600 text-white border-green-500"
                                : k === "⌫" || k === "⇧"
                                ? "flex-[1.5] bg-slate-700 text-slate-200 border-slate-600"
                                : isWide ? "flex-[4] bg-slate-800 text-slate-100 border-slate-700"
                                : "flex-1 bg-slate-800 text-slate-100 border-slate-700"
                            }`}
                          >
                            {k === "⇧" && shift ? "⇑" : k}
                          </button>
                        );
                      })}
                  </div>
                ))
              ) : (
                <div className="flex flex-col items-center pt-2">
                  {NUM_ROWS.map((row, ri) => (
                    <div key={ri} className="flex gap-3 mb-3">
                      {row.map(k => (
                        <button
                          key={k}
                          onPointerDown={(e) => { e.preventDefault(); handleKeyPress(k); }}
                          className={`w-24 h-16 rounded-xl text-2xl font-black border flex items-center justify-center transition-all active:scale-95 ${
                              k === "⌫" ? "bg-slate-700 text-slate-200 border-slate-600" : "bg-slate-800 text-slate-100 border-slate-700"
                          }`}
                        >{k}</button>
                      ))}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="w-48 flex flex-col gap-3">
              <button
                onPointerDown={(e) => { e.preventDefault(); setShowNumpad(p=>!p); }}
                className={`h-14 rounded-xl font-bold border transition-all flex items-center justify-center gap-2 uppercase tracking-widest text-xs ${
                    showNumpad ? "bg-primary/20 text-primary border-primary/30" : "bg-slate-800 text-slate-400 border-slate-700"
                }`}
              >
                <span className="material-symbols-outlined text-lg">{showNumpad ? "abc" : "123"}</span>
                {showNumpad ? "Litere" : "Cifre"}
              </button>

              <button
                onPointerDown={(e) => { e.preventDefault(); setSearchText(""); }}
                className="h-14 bg-slate-800 text-slate-400 border border-slate-700 rounded-xl font-bold flex items-center justify-center gap-2 uppercase tracking-widest text-xs transition-all hover:bg-slate-700"
              >
                <span className="material-symbols-outlined text-lg">delete</span>
                Șterge
              </button>

              <div className="flex-1" />

              <button
                onPointerDown={(e) => { e.preventDefault(); setIsSearching(false); }}
                className="h-20 bg-primary text-white border-none rounded-2xl font-black text-xl flex items-center justify-center gap-3 shadow-lg shadow-primary/20 transition-all active:scale-95"
              >
                <span className="material-symbols-outlined text-2xl">check_circle</span>
                GATA
              </button>

              <button
                onPointerDown={(e) => { e.preventDefault(); setIsSearching(false); }}
                className="h-12 bg-transparent text-slate-500 border border-slate-800 rounded-xl font-bold flex items-center justify-center gap-2 uppercase tracking-widest text-[10px] transition-all"
              >
                Închide (ESC)
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default function DelegationPlaceSelectorTest() {
    return (
        <APIProvider apiKey={GOOGLE_MAPS_API_KEY} libraries={['places']}>
            <PlaceSelectorContent />
        </APIProvider>
    );
}
