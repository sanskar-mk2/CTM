<script setup>
import { ref, computed } from "vue";
import { to_roman_numerical } from "@/util";
import dayjs from "dayjs";
import _ from "lodash";

const props = defineProps({
    projects: {
        type: Array,
        required: true,
    },
    months: {
        type: Array,
        required: false,
        default: () => [],
    },
    currencies: {
        type: Array,
        required: false,
        default: () => [],
    },
});

// Column definitions
const allColumns = ref([
    { key: "project_name", label: "Project Name", bold: true, visible: true, fixed: true },
    { key: "sponsor_name", label: "Sponsor Name", visible: true },
    { key: "contract_holder_country", label: "Contract Holder Country", visible: true },
    { key: "project_manager", label: "Project Manager", visible: true },
    { key: "currency", label: "Currency", visible: true },
    { key: "contract_value", label: "Contract Value", visible: true },
    { key: "contract_signed", label: "Contract Signed", visible: true },
    { key: "billing_type", label: "Billing Type", visible: true },
    { key: "activity_start_date", label: "Activity Start Date", visible: true },
    { key: "billing_start_date", label: "Billing Start Date", visible: true },
    { key: "clinical_duration", label: "Clinical Duration", visible: true },
    { key: "study_duration", label: "Study Duration", visible: true },
    { key: "patients", label: "Patients", visible: true },
    { key: "sites", label: "Sites", visible: true },
    { key: "status", label: "Status", visible: true },
    { key: "phase", label: "Phase", visible: true },
    { key: "therapeutic_area", label: "Therapeutic Area", visible: true },
    { key: "sponsor_country", label: "Sponsor Country", visible: true },
    { key: "amount_done", label: "Amount Done", visible: true },
]);

const visibleColumns = computed(() => allColumns.value.filter((c) => c.visible));

// Modal state
const columnsModalOpen = ref(false);

// Move column helpers
const moveUp = (index) => {
    if (index <= 1) return;
    const cols = allColumns.value;
    [cols[index - 1], cols[index]] = [cols[index], cols[index - 1]];
};

const moveDown = (index) => {
    const cols = allColumns.value;
    if (cols[index].fixed) return;
    if (index === cols.length - 1) return;
    [cols[index + 1], cols[index]] = [cols[index], cols[index + 1]];
};

// Helper for sumActual
const sumActual = (activities, locks) => {
    return _.sumBy(activities, (activity) => {
        const lockEntry = locks?.find((lock) =>
            dayjs(lock.date).isSame(dayjs(activity.date), "month")
        );
        return lockEntry && lockEntry.is_locked ? activity.value : 0;
    });
};

// Helper to sum activity value for a project for a given month
const sumActivityForMonth = (project, month) => {
    let total = 0;
    if (!project.groups) return 0;
    project.groups.forEach((group) => {
        (group.tasks || []).forEach((task) => {
            (task.activities || []).forEach((activity) => {
                if (activity.date && dayjs(activity.date).format('YYYY-MM') === month) {
                    total += activity.value * (task.price || 0);
                }
            });
        });
    });
    return total;
};

const getFinancialYear = (month) => {
    const d = dayjs(month + "-01");
    const year = d.year();
    const monthNum = d.month() + 1;
    if (monthNum >= 4) {
        return `${year}-${String(year + 1).slice(-2)}`;
    } else {
        return `${year - 1}-${String(year).slice(-2)}`;
    }
};

const financialYearData = computed(() => {
    if (!props.months || props.months.length === 0) {
        return {};
    }
    const grouped = _.groupBy(props.months, getFinancialYear);
    const sortedFYs = Object.keys(grouped).sort();
    const result = {};
    for (const fy of sortedFYs) {
        result[fy] = grouped[fy].sort((a, b) =>
            dayjs(a).isBefore(dayjs(b)) ? -1 : 1
        );
    }
    return result;
});

const getFYTotal = (project, monthsInFY) => {
    return _.sumBy(monthsInFY, (month) => sumActivityForMonth(project, month));
};

const cumulativeTotals = computed(() => {
    const totals = {};
    const allFYs = Object.keys(financialYearData.value).sort();
    props.projects.forEach((project) => {
        let cumulative = 0;
        allFYs.forEach((fy) => {
            const monthsInFY = financialYearData.value[fy];
            const fyTotal = getFYTotal(project, monthsInFY);
            cumulative += fyTotal;
            totals[`${project.id}-${fy}`] = cumulative;
        });
    });
    return totals;
});

const getCumulativeTotalUpToFY = (project, fy) => {
    return cumulativeTotals.value[`${project.id}-${fy}`] || 0;
};

// Helper to get exchange rate for a currency symbol
const getExchangeRate = (symbol) => {
    const found = props.currencies.find((c) => c.symbol === symbol);
    return found ? Number(found.exchange_rate_to_inr) : 1;
};

// Helper to format month as M/YY
const formatMonth = (month) => {
    return dayjs(month + '-01').format('MMM/YY');
};

const formatNumber = (value) => {
    if (typeof value !== "number") return value;
    return value.toLocaleString("en-US");
};

const formatInr = (value) => {
    if (typeof value !== "number") return value;
    return value.toLocaleString("en-IN", { maximumFractionDigits: 2 });
};

// Formatting for table & CSV
const formatCell = (project, colKey) => {
    switch (colKey) {
        case "status":
            return project.status ? "Active" : "Inactive";
        case "phase":
            return to_roman_numerical(project.phase);
        case "amount_done":
            if (!project.groups || !project.locks) return 0;
            const total = _.sumBy(project.groups, (group) =>
                _.sumBy(group.tasks || [], (task) =>
                    sumActual(task.activities || [], project.locks) * (task.price || 0)
                )
            );
            return Intl.NumberFormat("en-US").format(total);
        default:
            return project[colKey] ?? "-";
    }
};

const getChangeOrder = (balance) => {
    return balance < 0 ? Math.abs(balance) : 0;
};

// CSV export
const exportCSV = () => {
    const cols = visibleColumns.value;

    const dynamicHeaders = [];
    Object.entries(financialYearData.value).forEach(([fy, months]) => {
        months.forEach((month) => {
            dynamicHeaders.push(formatMonth(month));
            dynamicHeaders.push(`${formatMonth(month)} (INR)`);
        });
        dynamicHeaders.push(`FY ${fy} Total`);
        dynamicHeaders.push(`FY ${fy} Total (INR)`);
        dynamicHeaders.push(`Balance`);
        dynamicHeaders.push(`Balance (INR)`);
        dynamicHeaders.push(`Change Order`);
        dynamicHeaders.push(`Change Order (INR)`);
    });
    const headers = [...cols.map((c) => c.label), ...dynamicHeaders];

    const rows = props.projects.map((proj) => {
        const base = cols.map((c) => {
            const val = formatCell(proj, c.key);
            return `"${String(val ?? "").replace(/"/g, '""')}"`;
        });

        const dynamicCells = [];
        const exchangeRate = getExchangeRate(proj.currency);
        Object.entries(financialYearData.value).forEach(([fy, months]) => {
            months.forEach((month) => {
                const monthTotal = sumActivityForMonth(proj, month);
                dynamicCells.push(`"${formatNumber(monthTotal)}"`);
                dynamicCells.push(
                    `"${formatInr(monthTotal * exchangeRate)}"`
                );
            });

            const fyTotal = getFYTotal(proj, months);
            dynamicCells.push(`"${formatNumber(fyTotal)}"`);
            dynamicCells.push(`"${formatInr(fyTotal * exchangeRate)}"`);

            const cumulativeTotal = getCumulativeTotalUpToFY(proj, fy);
            let balance = (proj.contract_value || 0) - cumulativeTotal;
            let changeOrder = getChangeOrder(balance);
            if (balance < 0) balance = 0;
            dynamicCells.push(`"${formatNumber(balance)}"`);
            dynamicCells.push(`"${formatInr(balance * exchangeRate)}"`);
            dynamicCells.push(`"${formatNumber(changeOrder)}"`);
            dynamicCells.push(`"${formatInr(changeOrder * exchangeRate)}"`);
        });

        return [...base, ...dynamicCells].join(",");
    });
    const csvContent = [headers.join(","), ...rows].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "projects.csv");
    link.click();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <div>
        <!-- Action buttons -->
        <div class="flex justify-end mb-2 gap-2">
            <button class="btn btn-sm" @click="columnsModalOpen = true">
                Customize Columns
            </button>
            <button class="btn btn-sm btn-outline" @click="exportCSV">
                Export CSV
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th
                            v-for="col in visibleColumns"
                            :key="col.key"
                            :class="{
                                'sticky left-0 z-10 bg-base-100': col.fixed,
                            }"
                        >
                            {{ col.label }}
                        </th>
                        <template
                            v-for="(months, fy) in financialYearData"
                            :key="fy"
                        >
                            <template v-for="month in months" :key="month">
                                <th>{{ formatMonth(month) }}</th>
                                <th>{{ formatMonth(month) }} (INR)</th>
                            </template>
                            <th>FY {{ fy }} Total</th>
                            <th>FY {{ fy }} Total (INR)</th>
                            <th>Balance</th>
                            <th>Balance (INR)</th>
                            <th>Change Order</th>
                            <th>Change Order (INR)</th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(project, index) in projects" :key="project.id">
                        <td
                            v-for="col in visibleColumns"
                            :key="col.key"
                            :class="{
                                'font-bold': col.bold,
                                'sticky left-0 z-10': col.fixed,
                                'bg-base-200': col.fixed && index % 2 === 1,
                                'bg-base-100': col.fixed && index % 2 === 0,
                            }"
                        >
                            {{ formatCell(project, col.key) }}
                        </td>
                        <template
                            v-for="(monthsInFY, fy) in financialYearData"
                            :key="fy"
                        >
                            <template v-for="month in monthsInFY" :key="month">
                                <td>{{ formatNumber(sumActivityForMonth(project, month)) }}</td>
                                <td>
                                    {{
                                        formatInr(sumActivityForMonth(project, month) * getExchangeRate(project.currency))
                                    }}
                                </td>
                            </template>
                            <td class="font-bold">
                                {{ formatNumber(getFYTotal(project, monthsInFY)) }}
                            </td>
                            <td class="font-bold">
                                {{ formatInr(getFYTotal(project, monthsInFY) * getExchangeRate(project.currency)) }}
                            </td>
                            <td class="font-bold">
                                {{
                                    formatNumber(
                                        Math.max(0, (project.contract_value || 0) - getCumulativeTotalUpToFY(project, fy))
                                    )
                                }}
                            </td>
                            <td class="font-bold">
                                {{
                                    formatInr(
                                        Math.max(0, (project.contract_value || 0) - getCumulativeTotalUpToFY(project, fy)) *
                                            getExchangeRate(project.currency)
                                    )
                                }}
                            </td>
                            <td class="font-bold">
                                {{
                                    formatNumber(
                                        getChangeOrder((project.contract_value || 0) - getCumulativeTotalUpToFY(project, fy))
                                    )
                                }}
                            </td>
                            <td class="font-bold">
                                {{
                                    formatInr(
                                        getChangeOrder((project.contract_value || 0) - getCumulativeTotalUpToFY(project, fy)) *
                                            getExchangeRate(project.currency)
                                    )
                                }}
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Column Customization Modal -->
        <div
            class="modal"
            :class="{ 'modal-open': columnsModalOpen }"
            @keydown.escape="columnsModalOpen = false"
        >
            <div class="modal-box w-11/12 max-w-2xl relative">
                <h3 class="font-bold text-lg mb-4">Customize Columns</h3>
                <button class="btn btn-sm btn-circle absolute right-2 top-2" @click="columnsModalOpen = false">✕</button>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Visible</th>
                                <th>Column</th>
                                <th class="text-center">Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(col, idx) in allColumns" :key="col.key">
                                <td>
                                    <input
                                        type="checkbox"
                                        v-model="col.visible"
                                        class="checkbox"
                                        :disabled="col.fixed"
                                    />
                                </td>
                                <td>{{ col.label }}</td>
                                <td class="flex gap-2 justify-center">
                                    <button
                                        class="btn btn-xs btn-circle"
                                        @click="moveUp(idx)"
                                        :disabled="idx <= 1 || col.fixed"
                                    >
                                        ▲
                                    </button>
                                    <button
                                        class="btn btn-xs btn-circle"
                                        @click="moveDown(idx)"
                                        :disabled="idx === allColumns.length - 1 || col.fixed"
                                    >
                                        ▼
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-action">
                    <button class="btn" @click="columnsModalOpen = false">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-open {
    opacity: 1;
    pointer-events: auto;
}
</style>