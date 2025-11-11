import Swal from "sweetalert2";
import "animate.css";

export const ymd = (d) => {
  const pad = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

export function presetToRange(preset) {
  const today = new Date();
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  const endOfMonth   = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  const prevStart    = new Date(today.getFullYear(), today.getMonth() - 1, 1);
  const prevEnd      = new Date(today.getFullYear(), today.getMonth(), 0);
  const yesterday    = new Date(today); yesterday.setDate(today.getDate() - 1);
  const weekStart    = new Date(today); weekStart.setDate(today.getDate() - 6);

  switch (preset) {
    case "all":       return { desde: "", hasta: "" };
    case "today":     return { desde: ymd(today),     hasta: ymd(today) };
    case "yesterday": return { desde: ymd(yesterday), hasta: ymd(yesterday) };
    case "last7":     return { desde: ymd(weekStart), hasta: ymd(today) };
    case "month":     return { desde: ymd(startOfMonth), hasta: ymd(endOfMonth) };
    case "prevmonth": return { desde: ymd(prevStart),   hasta: ymd(prevEnd) };
    default:          return { desde: "", hasta: "" };
  }
}

export const debounce = (fn, ms) => { let t=null; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

/**
 * Sin CSS. Solo un fallback de cierre por si algún estilo externo
 * llegara a bloquear los clics de la X o Cancelar.
 */
export function ensureSwalStyles() {
  if (window.__sw_report_fallback_bound) return;
  window.__sw_report_fallback_bound = true;

  document.addEventListener(
    "click",
    (ev) => {
      const popup  = Swal.getPopup();
      if (!popup) return;
      const cancel = Swal.getCancelButton();
      const close  = Swal.getCloseButton();
      if (cancel && (ev.target === cancel || cancel.contains(ev.target))) {
        Swal.close();
      }
      if (close && (ev.target === close || close.contains(ev.target))) {
        Swal.close();
      }
    },
    { capture: true }
  );
}

export { Swal };
