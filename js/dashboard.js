document.addEventListener("DOMContentLoaded", () => {

    let chart;
    let previousValues = {};
    let isLoading = false;
    let autoRefresh;

    /* =========================
       GET CSS VARIABLES (THEME)
    ========================= */
    function getChartColors() {

        const root = document.documentElement;
        const styles = getComputedStyle(root);

        return {
            line: styles.getPropertyValue("--chart-line").trim(),
            fill: styles.getPropertyValue("--chart-fill").trim()
        };
    }

    /* =========================
       INSTANT CHART THEME UPDATE
    ========================= */
    function updateChartThemeInstant() {

        if (!chart) return;

        const colors = getChartColors();

        chart.data.datasets.forEach(ds => {
            ds.borderColor = colors.line;
            ds.backgroundColor = colors.fill;
        });

        chart.update();
    }

    /* =========================
       THEME CHANGE DETECTOR
    ========================= */
    const observer = new MutationObserver(() => {
        updateChartThemeInstant();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class"]
    });

    /* =========================
       GET MONTH — reads from URL ?month= parameter
       (custom calendar picker navigates via URL directly)
    ========================= */
    function getSelectedMonth() {
        const params = new URLSearchParams(window.location.search);
        return params.get("month") || "";
    }

    /* =========================
       COUNTER ANIMATION
    ========================= */
    function animateCounter(el, target) {

        target = parseInt(target) || 0;

        let current = parseInt(el.innerText.replace(/,/g, "")) || 0;

        const duration = 700;
        const steps = 30;
        const increment = (target - current) / steps;

        clearInterval(el.counterInterval);

        el.counterInterval = setInterval(() => {

            current += increment;

            if (
                (increment > 0 && current >= target) ||
                (increment < 0 && current <= target)
            ) {
                current = target;
                clearInterval(el.counterInterval);
            }

            el.innerText = Math.floor(current).toLocaleString();

        }, duration / steps);

        el.classList.add("pulse");

        setTimeout(() => {
            el.classList.remove("pulse");
        }, 500);
    }

    function safe(val) {
        return parseInt(val) || 0;
    }

    /* =========================
       LOAD DATA
    ========================= */
    async function loadData() {

        if (isLoading) return;

        isLoading = true;

        try {

            const month = getSelectedMonth();

            const response = await fetch(
                "dashboard_api.php?month=" + encodeURIComponent(month)
            );

            const data = await response.json();

            /* =========================
               UPDATE COUNTERS
            ========================= */
            document.querySelectorAll(".counter").forEach(el => {

                const key = el.getAttribute("data-key");

                if (!key) return;

                const newValue = safe(data[key]);
                const oldValue = previousValues[key] ?? null;

                if (newValue !== oldValue) {
                    animateCounter(el, newValue);
                }

                previousValues[key] = newValue;
            });

            /* =========================
               UPDATE CHART DATA
            ========================= */
            if (chart && Array.isArray(data.chart)) {

                chart.data.datasets[0].data = data.chart;

                const month = getSelectedMonth();

                if (month) {

                    const selectedDate = new Date(month + "-01");

                    const monthName = selectedDate.toLocaleString(
                        "default",
                        { month: "short" }
                    );

                    chart.data.labels = ["","","","","","","","","","","",""];

                    const monthIndex = selectedDate.getMonth();

                    chart.data.labels[monthIndex] = monthName;

                } else {

                    chart.data.labels = [
                        "Jan","Feb","Mar","Apr",
                        "May","Jun","Jul","Aug",
                        "Sep","Oct","Nov","Dec"
                    ];
                }

                chart.update();
            }

        } catch (err) {
            console.error("Dashboard API Error:", err);
        }

        isLoading = false;
    }

    /* =========================
       CHART INIT
    ========================= */
    const canvas = document.getElementById("mainChart");

    if (canvas && typeof Chart !== "undefined") {

        if (window.mainDashboardChart) {
            window.mainDashboardChart.destroy();
        }

        const colors = getChartColors();

        chart = new Chart(canvas.getContext("2d"), {

            type: "line",

            data: {

                labels: [
                    "Jan","Feb","Mar","Apr",
                    "May","Jun","Jul","Aug",
                    "Sep","Oct","Nov","Dec"
                ],

                datasets: [{
                    label: "Services",
                    data: window.chartData || [],

                    borderColor: colors.line,
                    backgroundColor: colors.fill,

                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: { display: false }
                },

                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        window.mainDashboardChart = chart;
    }

    /* =========================
       INITIAL LOAD
       Only fetch from API if dashboard_api.php exists.
       PHP already rendered the correct filtered values,
       so we skip the first loadData() call to avoid
       overwriting PHP-rendered counters with API data.
       loadData() still runs on auto-refresh to keep
       data live, but now reads month from URL correctly.
    ========================= */
    loadData();

    /* =========================
       AUTO REFRESH — every 30 seconds (was 5s)
       5 seconds was too aggressive and caused the
       "flicker back to all data" effect on slow connections.
    ========================= */
    clearInterval(autoRefresh);
    
    autoRefresh = setInterval(() => {
        loadData();
    }, 30000);


   
    /* =========================
       CLIENT CARD TOGGLE
    ========================= */
    const clientCard = document.querySelector(".client-card");

    if (clientCard) {

        clientCard.addEventListener("click", e => {
            e.stopPropagation();
            clientCard.classList.toggle("active");
        });

        document.addEventListener("click", () => {
            clientCard.classList.remove("active");
        });
    }

});