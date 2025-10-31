<template>
  <main class="container-fluid py-3 py-lg-4 reportes-page">
    <!-- Header -->
    <div class="mb-3">
      <h1 class="title-page animate__animated animate__fadeInDown">Reportes</h1>
      <p class="subtitle text-muted">Visualiza y descarga estadísticas del sistema.</p>
    </div>

    <!-- Selector (6 cards) -->
    <div class="report-grid">
      <div
        v-for="r in cards"
        :key="r.key"
        class="report-card position-relative"
        :data-accent="r.accent"
        :class="{ active: active===r.key }"
        @click="active=r.key"
        ref="cardRefs"
        data-animate-on-scroll
      >
        <!-- Acciones por card -->
        <div class="card-actions">
          <button type="button" class="btn btn-outline-danger btn-xs" @click.stop="exportar(r.key,'pdf')">
            <i class="bi bi-file-earmark-pdf"></i> PDF
          </button>
          <button type="button" class="btn btn-outline-success btn-xs" @click.stop="exportar(r.key,'excel')">
            <i class="bi bi-file-earmark-excel"></i> Excel
          </button>
        </div>

        <div class="icon-wrap">
          <i :class="r.icon"></i>
        </div>
        <div class="label">{{ r.label }}</div>
        <div class="hint">Exportar reporte</div>
        <span class="stroke"></span>
      </div>
    </div>

    <!-- Nota suave inferior -->
    <div class="soft-footer mt-3">
      <small class="text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Da clic en <strong>PDF</strong> o <strong>Excel</strong> dentro de cada tarjeta para abrir los filtros.
      </small>
    </div>
  </main>
</template>

<script>
import "animate.css";
import api from "@/assets/js/Reportes.js";

export default {
  name: "Reportes",
  data() {
    return {
      active: "top",
      cards: [
        { key: "top",   label: "Top técnicas",           icon: "bi bi-bar-chart",       accent: "violet" },
        { key: "act",   label: "Actividades alumno",     icon: "bi bi-list-check",      accent: "cyan"   },
        { key: "citas", label: "Citas por alumno",       icon: "bi bi-calendar2-check", accent: "blue"   },
        { key: "bit",   label: "Bitácoras por alumno",   icon: "bi bi-journal-check",   accent: "green"  },
        { key: "enc",   label: "Resultados encuestas",   icon: "bi bi-graph-up",        accent: "pink"   },
        { key: "rec",   label: "Recompensas canjeadas",  icon: "bi bi-gift",            accent: "orange" },
      ],
    };
  },
  mounted() {
    this.applyScrollAnimations();
    this.addTiltToCards();
  },
  methods: {
    exportar(key, tipo) {
      api.openExportDialog(key, tipo);
    },

    // Animaciones on-scroll
    applyScrollAnimations() {
      const els = document.querySelectorAll("[data-animate-on-scroll]");
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((e) => {
            if (e.isIntersecting) {
              e.target.classList.add("animate__animated", "animate__fadeInUp");
              io.unobserve(e.target);
            }
          });
        },
        { threshold: 0.1 }
      );
      els.forEach((el) => io.observe(el));
    },

    // Tilt 3D + hover lift
    addTiltToCards() {
      const cards = this.$refs.cardRefs instanceof Array ? this.$refs.cardRefs : [this.$refs.cardRefs];
      cards.forEach((card) => {
        let raf = null;
        const onMove = (e) => {
          const r = card.getBoundingClientRect();
          const cx = e.clientX - r.left, cy = e.clientY - r.top;
          const rx = ((cy / r.height) - 0.5) * -9;
          const ry = ((cx / r.width) - 0.5) *  9;
          if (raf) cancelAnimationFrame(raf);
          raf = requestAnimationFrame(() => {
            card.style.setProperty("--rx", rx.toFixed(2) + "deg");
            card.style.setProperty("--ry", ry.toFixed(2) + "deg");
          });
        };
        const reset = () => {
          if (raf) cancelAnimationFrame(raf);
          card.style.setProperty("--rx", "0deg");
          card.style.setProperty("--ry", "0deg");
        };
        card.addEventListener("mousemove", onMove);
        card.addEventListener("mouseleave", reset);
      });
    },
  },
};
</script>

<style scoped>
@import "@/assets/css/Reportes.css";
</style>
