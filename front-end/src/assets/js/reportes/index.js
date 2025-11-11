// src/assets/js/reportes/index.js
import { apiBase, authHeaders } from "./common/apiClient";

// Per-report modules
import { openTopTecnicasDialog, getTopTecnicas, exportTopTecnicas } from "./topTecnicas";
import { openActividadesAlumnoDialog, getActividadesAlumno, exportActividadesAlumno } from "./actividadesAlumno";
import { openCitasAlumnoDialog, getCitasAlumno, exportCitasAlumno } from "./citasAlumno";
import { openBitacorasAlumnoDialog, getBitacorasAlumno, exportBitacorasAlumno } from "./bitacorasAlumno";
import { openEncuestasResultadosDialog, getEncuestasResultados, exportEncuestasResultados } from "./encuestasResultados";
import { openRecompensasCanjeadasDialog, getRecompensasCanjeadas, exportRecompensasCanjeadas } from "./recompensasCanjeadas";

const OPEN_MAP = {
  top:   openTopTecnicasDialog,
  act:   openActividadesAlumnoDialog,
  citas: openCitasAlumnoDialog,
  bit:   openBitacorasAlumnoDialog,
  enc:   openEncuestasResultadosDialog,
  rec:   openRecompensasCanjeadasDialog,
};

const GET_MAP = {
  top:   getTopTecnicas,
  act:   getActividadesAlumno,
  citas: getCitasAlumno,
  bit:   getBitacorasAlumno,
  enc:   getEncuestasResultados,
  rec:   getRecompensasCanjeadas,
};

const EXPORT_MAP = {
  top:   exportTopTecnicas,
  act:   exportActividadesAlumno,
  citas: exportCitasAlumno,
  bit:   exportBitacorasAlumno,
  enc:   exportEncuestasResultados,
  rec:   exportRecompensasCanjeadas,
};

function openExportDialog(key, tipo = "pdf") {
  const fn = OPEN_MAP[key];
  if (!fn) throw new Error("Reporte no reconocido");
  return fn(tipo);
}

function getReport(key, filtros = {}) {
  const fn = GET_MAP[key];
  if (!fn) throw new Error("Reporte no reconocido");
  return fn(filtros);
}

function exportReport(key, tipo = "pdf", filtros = {}) {
  const fn = EXPORT_MAP[key];
  if (!fn) throw new Error("Reporte no reconocido");
  return fn(tipo, filtros);
}

export default {
  apiBase,
  authHeaders,
  openExportDialog,
  getReport,
  exportReport,
};
