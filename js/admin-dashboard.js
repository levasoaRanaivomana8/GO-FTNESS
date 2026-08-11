/* =========================================================
   GO-FITNESS — Admin Dashboard JS (LOCAL)
   - Premium KPI count-up animation
   - Chart.js theme: Rouge / Or / Noir
   - Fetch KPI/Table + Charts
   - Live search
   - Sidebar auto-collapse <= 992px + manual toggle
   ========================================================= */

(() => {
  "use strict";

  // ✅ Guard: évite double-initialisation (sinon Chart.js peut se recréer en boucle)
  if (window.__GF_ADMIN_DASH_INITED) return;
  window.__GF_ADMIN_DASH_INITED = true;

  // ----- Config / Colors (match CSS vars)
  const COLORS = {
    red: "#ff1e1e",
    gold: "#f5c542",
    black: "#0b0b0b",
    blue: "#2b7cff",
    brown: "#8b5a2b",
    grid: "rgba(0,0,0,.08)",
    text: "rgba(11,11,11,.85)",
    muted: "rgba(11,11,11,.55)",
  };

  // BASE URL (support ancien dashboard + layout commun)
  const BASE = (
    (window.GF && window.GF.baseUrl) ||
    window.GF_BASE_URL ||
    ""
  ).replace(/\/$/, "");
  const ENDPOINT_DATA = `${BASE}/admin/dashboard/data`;
  const ENDPOINT_CHARTS = `${BASE}/admin/dashboard/charts`;

  // ----- DOM
  const el = (id) => document.getElementById(id);

  const loader = el("gfLoader");

  const kpiActive = el("kpiActive");
  const kpiMonth = el("kpiMonth");
  const kpiTotal = el("kpiTotal");

  const periodSelect = el("periodSelect");
  const startDate = el("startDate");
  const endDate = el("endDate");
  const btnApply = el("btnApply");

  const monthSelect = el("monthSelect");
  const btnMonth = el("btnMonth");

  const btnReload = el("btnReload");
  const searchInput = el("searchInput");

  const subsTbody = el("subsTbody");

  const pieFooter = el("pieFooter");

  const canvasLine = el("chartLine");
  const canvasPie = el("chartPie");
  const canvasBar = el("chartBar");

  // ----- State
  let chartLine = null;
  let chartPie = null;
  let chartBar = null;

  let tableRowsCache = [];

  // Dernières données charts (pour pouvoir re-render proprement au resize)
  let lastChartsPayload = null;

  // Anti "infinite resize" (Chart.js + responsive + containers flex)
  // => On force une taille fixe au canvas et on désactive responsive.
  function fixCanvasSize(canvas) {
    if (!canvas) return;
    const box = canvas.parentElement;
    if (!box) return;

    // parent height doit être stable via CSS (.gf-chart-box)
    const w = Math.max(10, box.clientWidth || 0);
    const h = Math.max(10, box.clientHeight || 0);

    // Les attributs width/height (pas seulement CSS) stabilisent Chart.js.
    canvas.width = w;
    canvas.height = h;
  }

  let resizeTimer = null;
  function scheduleChartsRerender() {
    if (!lastChartsPayload) return;
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      try {
        destroyCharts();
        renderLine(lastChartsPayload.revenueByMonth);
        renderPie(lastChartsPayload.typeSharePie);
        renderBar(lastChartsPayload.modeByTypeBar);
      } catch (_) {
        // ignore
      }
    }, 160);
  }

  window.addEventListener("resize", scheduleChartsRerender);

  // =========================================================
  // Premium helpers
  // =========================================================
  const fmtMoney = (n) => {
    const v = Number(n || 0);
    // "Ar" formatting
    return v.toLocaleString("fr-FR", { maximumFractionDigits: 0 }) + " Ar";
  };

  const fmtInt = (n) => {
    const v = Number(n || 0);
    return v.toLocaleString("fr-FR", { maximumFractionDigits: 0 });
  };

  function setLoadingKpis(isLoading) {
    [kpiActive, kpiMonth, kpiTotal].forEach((node) => {
      if (!node) return;
      node.classList.toggle("is-loading", !!isLoading);
    });
  }

  // Count-up animation
  function animateValue(node, toValue, { duration = 900, money = false } = {}) {
    if (!node) return;

    const start = performance.now();
    const fromValue = Number(node.dataset.value || 0);
    const target = Number(toValue || 0);

    node.dataset.value = String(target);

    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

    const tick = (now) => {
      const t = Math.min(1, (now - start) / duration);
      const eased = easeOutCubic(t);
      const current = fromValue + (target - fromValue) * eased;

      node.textContent = money ? fmtMoney(current) : fmtInt(current);

      if (t < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  }

  // =========================================================
  // Sidebar responsive
  // =========================================================
  (function sidebarAuto() {
    const sidebar = el("gfSidebar");
    const btn = el("btnSidebar");
    // Dans la version "layout commun" (layouts/app.php), la gestion sidebar
    // est déjà faite par assets/js/app.js (classes: is-collapsed).
    // On garde ici la compatibilité ancienne uniquement.
    if (!sidebar || !btn) return;

    const applyAuto = () => {
      if (window.innerWidth <= 992) sidebar.classList.add("collapsed");
      else sidebar.classList.remove("collapsed");
    };

    window.addEventListener("resize", applyAuto);
    applyAuto();

    btn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
    });
  })();

  // =========================================================
  // Chart.js Theme
  // =========================================================
  function applyChartTheme() {
    if (!window.Chart) return;

    // global defaults
    Chart.defaults.font.family = "Poppins, system-ui, Arial";
    Chart.defaults.color = COLORS.text;

    // tooltip
    Chart.defaults.plugins.tooltip.backgroundColor = "rgba(11,11,11,.92)";
    Chart.defaults.plugins.tooltip.titleColor = "#fff";
    Chart.defaults.plugins.tooltip.bodyColor = "#fff";
    Chart.defaults.plugins.tooltip.borderColor = "rgba(245,197,66,.40)";
    Chart.defaults.plugins.tooltip.borderWidth = 1;

    // legend
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
  }

  // Helper for axis styling
  function axisCommon() {
    return {
      grid: { color: COLORS.grid },
      ticks: { color: COLORS.muted, font: { weight: "700" } },
      border: { color: "rgba(0,0,0,.10)" },
    };
  }

  // =========================================================
  // Fetch helpers
  // =========================================================
  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { "Accept": "application/json" } });
    if (!res.ok) {
      const txt = await res.text().catch(() => "");
      throw new Error(`HTTP ${res.status} ${res.statusText} — ${txt}`);
    }
    return await res.json();
  }

  function hideLoader() {
    if (!loader) return;
    loader.classList.add("hidden");
  }

  function fatal(msg) {
    console.error(msg);
    hideLoader();
    if (subsTbody) {
      subsTbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center py-4" style="color:#b00020;font-weight:900;">
            ${String(msg)}
          </td>
        </tr>
      `;
    }
  }

  // =========================================================
  // Table render
  // =========================================================
  function badgeClass(days) {
    const d = Number(days);
    if (d <= 5) return "gf-badge gf-badge-danger";
    if (d <= 10) return "gf-badge gf-badge-warn";
    return "gf-badge gf-badge-ok";
  }

  function renderTable(rows) {
    tableRowsCache = Array.isArray(rows) ? rows : [];

    if (!subsTbody) return;

    if (!tableRowsCache.length) {
      subsTbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center py-4" style="color:rgba(11,11,11,.55);font-weight:900;">
            Aucun abonné actif pour l’instant.
          </td>
        </tr>
      `;
      return;
    }

    subsTbody.innerHTML = tableRowsCache
      .map((r) => {
        const nom = escapeHtml(r.nom ?? "");
        const prenom = escapeHtml(r.prenom ?? "");
        const type = escapeHtml(r.type ?? "");
        const mode = escapeHtml(r.mode ?? "");
        const datefin = escapeHtml(r.datefin ?? "");
        const jr = Number(r.jours_restants ?? 0);

        return `
          <tr>
            <td>${nom}</td>
            <td>${prenom}</td>
            <td><b>${type}</b></td>
            <td>${mode}</td>
            <td>${datefin}</td>
            <td class="text-end">
              <span class="${badgeClass(jr)}">${fmtInt(jr)} j</span>
            </td>
          </tr>
        `;
      })
      .join("");
  }

  function applySearch(query) {
    const q = String(query || "").trim().toLowerCase();
    if (!q) return renderTable(tableRowsCache);

    const filtered = tableRowsCache.filter((r) => {
      const nom = String(r.nom || "").toLowerCase();
      const prenom = String(r.prenom || "").toLowerCase();
      return nom.includes(q) || prenom.includes(q);
    });

    if (!subsTbody) return;
    if (!filtered.length) {
      subsTbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center py-4" style="color:rgba(11,11,11,.55);font-weight:900;">
            Aucun résultat.
          </td>
        </tr>
      `;
      return;
    }

    subsTbody.innerHTML = filtered
      .map((r) => {
        const nom = escapeHtml(r.nom ?? "");
        const prenom = escapeHtml(r.prenom ?? "");
        const type = escapeHtml(r.type ?? "");
        const mode = escapeHtml(r.mode ?? "");
        const datefin = escapeHtml(r.datefin ?? "");
        const jr = Number(r.jours_restants ?? 0);

        return `
          <tr>
            <td>${nom}</td>
            <td>${prenom}</td>
            <td><b>${type}</b></td>
            <td>${mode}</td>
            <td>${datefin}</td>
            <td class="text-end">
              <span class="${badgeClass(jr)}">${fmtInt(jr)} j</span>
            </td>
          </tr>
        `;
      })
      .join("");
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // =========================================================
  // Charts render (theme Rouge/Or/Noir)
  // =========================================================
  function destroyCharts() {
    [chartLine, chartPie, chartBar].forEach((c) => {
      try { c && c.destroy(); } catch(_) {}
    });
    chartLine = chartPie = chartBar = null;
  }

  function renderLine(revenueByMonth) {
    if (!canvasLine || !window.Chart) return;

    fixCanvasSize(canvasLine);

    const labels = revenueByMonth?.labels || [];
    const data = revenueByMonth?.data || [];

    chartLine = new Chart(canvasLine, {
      type: "line",
      data: {
        labels,
        datasets: [{
          label: "Revenu",
          data,
          borderColor: COLORS.red,
          backgroundColor: "rgba(255,30,30,.12)",
          pointBackgroundColor: COLORS.black,
          pointBorderColor: COLORS.gold,
          pointRadius: 3,
          borderWidth: 3,
          tension: 0.35,
          fill: true,
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${fmtMoney(ctx.parsed.y)}`
            }
          }
        },
        scales: {
          x: axisCommon(),
          y: {
            ...axisCommon(),
            ticks: {
              ...axisCommon().ticks,
              callback: (v) => fmtMoney(v)
            }
          }
        }
      }
    });
  }

  function renderPie(typeSharePie) {
    if (!canvasPie || !window.Chart) return;

    fixCanvasSize(canvasPie);

    const labels = typeSharePie?.labels || [];
    const values = typeSharePie?.values || [];
    const percent = typeSharePie?.percent || [];

    // colors per type
    const colorMap = {
      "Normal": COLORS.red,
      "Premium": COLORS.gold,
      "VIP": COLORS.black,
    };
    const bg = labels.map((l) => colorMap[l] || COLORS.blue);

    chartPie = new Chart(canvasPie, {
      type: "doughnut",
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: bg,
          borderColor: "rgba(255,255,255,.92)",
          borderWidth: 2,
          hoverOffset: 8,
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: {
          legend: { position: "bottom" },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const v = ctx.parsed;
                return ` ${ctx.label}: ${fmtMoney(v)}`;
              }
            }
          }
        }
      }
    });

    // Footer percent badges
    if (pieFooter) {
      pieFooter.innerHTML = labels.map((l, i) => {
        const p = percent[i] ?? 0;
        return `<div class="gf-pie-item"><b>${escapeHtml(l)}</b> <span>${String(p)}%</span></div>`;
      }).join("");
    }
  }

  function renderBar(modeByTypeBar) {
    if (!canvasBar || !window.Chart) return;

    fixCanvasSize(canvasBar);

    const labels = modeByTypeBar?.labels || ["Normal", "Premium", "VIP"];
    const mensuel = modeByTypeBar?.mensuel || [0,0,0];
    const seance = modeByTypeBar?.seance || [0,0,0];

    chartBar = new Chart(canvasBar, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            label: "Mensuel",
            data: mensuel,
            backgroundColor: "rgba(255,30,30,.85)",
            borderColor: COLORS.red,
            borderWidth: 1,
            borderRadius: 10,
          },
          {
            label: "Séance",
            data: seance,
            backgroundColor: "rgba(245,197,66,.85)",
            borderColor: COLORS.gold,
            borderWidth: 1,
            borderRadius: 10,
          }
        ]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "top" },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${ctx.dataset.label}: ${fmtInt(ctx.parsed.y)}`
            }
          }
        },
        scales: {
          x: axisCommon(),
          y: {
            ...axisCommon(),
            beginAtZero: true,
            ticks: {
              ...axisCommon().ticks,
              precision: 0,
              callback: (v) => fmtInt(v)
            }
          }
        }
      }
    });
  }

  // =========================================================
  // Loaders / animations
  // =========================================================
  function enableAnim() {
    const items = document.querySelectorAll(".gf-anim");
    if (!items.length) return;
    // small stagger
    items.forEach((node, idx) => {
      setTimeout(() => node.classList.add("on"), 60 * idx);
    });
  }

  // =========================================================
  // Main load
  // =========================================================
  function getPeriodQuery() {
    const period = (periodSelect?.value || "month");
    const s = (startDate?.value || "").trim();
    const e = (endDate?.value || "").trim();

    const params = new URLSearchParams();
    if (s && e) {
      params.set("start", s);
      params.set("end", e);
    } else {
      params.set("period", period);
    }
    return params.toString();
  }

  function getMonth() {
    // input type month => YYYY-MM
    const m = (monthSelect?.value || "").trim();
    if (m) return m;
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    return `${d.getFullYear()}-${mm}`;
  }

  async function loadDataAndTable() {
    setLoadingKpis(true);

    const qs = getPeriodQuery();
    const url = `${ENDPOINT_DATA}?${qs}`;

    const json = await fetchJSON(url);
    if (!json || json.ok !== true) throw new Error("Réponse data invalide");

    // KPI animation
    animateValue(kpiActive, json.kpi?.activeSubscribers ?? 0, { duration: 900, money: false });
    animateValue(kpiMonth, json.kpi?.revenueCurrentMonth ?? 0, { duration: 1000, money: true });
    animateValue(kpiTotal, json.kpi?.revenueTotal ?? 0, { duration: 1100, money: true });

    setLoadingKpis(false);

    // Table
    renderTable(json.table || []);
  }

  async function loadCharts() {
    const month = getMonth();
    if (monthSelect && !monthSelect.value) monthSelect.value = month;

    const url = `${ENDPOINT_CHARTS}?month=${encodeURIComponent(month)}`;
    const json = await fetchJSON(url);
    if (!json || json.ok !== true) throw new Error("Réponse charts invalide");

    // conserve pour re-render au resize (anti boucle Chart.js)
    lastChartsPayload = json.charts || null;

    destroyCharts();
    renderLine(lastChartsPayload?.revenueByMonth);
    renderPie(lastChartsPayload?.typeSharePie);
    renderBar(lastChartsPayload?.modeByTypeBar);
  }

  async function boot() {
    try {
      // default month (for charts)
      if (monthSelect && !monthSelect.value) {
        const m = getMonth();
        monthSelect.value = m;
      }

      applyChartTheme();
      enableAnim();

      await loadDataAndTable();
      await loadCharts();

      hideLoader();

      // keep loader from ever sticking
      setTimeout(hideLoader, 2500);

      console.log("GF: BOOT OK ✅");
    } catch (e) {
      fatal(e?.message || e);
    }
  }

  // =========================================================
  // Events
  // =========================================================
  btnApply?.addEventListener("click", async () => {
    try {
      setLoadingKpis(true);
      await loadDataAndTable();
      setLoadingKpis(false);
    } catch (e) {
      fatal(e?.message || e);
    }
  });

  btnReload?.addEventListener("click", async () => {
    try {
      setLoadingKpis(true);
      await loadDataAndTable();
      await loadCharts();
      setLoadingKpis(false);
    } catch (e) {
      fatal(e?.message || e);
    }
  });

  btnMonth?.addEventListener("click", async () => {
    try {
      await loadCharts();
    } catch (e) {
      fatal(e?.message || e);
    }
  });

  searchInput?.addEventListener("input", (ev) => {
    applySearch(ev.target.value);
  });

  // Run
  window.addEventListener("load", boot);
})();