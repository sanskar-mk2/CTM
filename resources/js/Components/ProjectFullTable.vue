<script setup>
import { ref, computed } from "vue";
import { to_roman_numerical } from "@/util";

const props = defineProps({
    projects: {
        type: Array,
        required: true,
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

// Formatting for table & CSV
const formatCell = (project, colKey) => {
    switch (colKey) {
        case "status":
            return project.status ? "Active" : "Inactive";
        case "phase":
            return to_roman_numerical(project.phase);
        default:
            return project[colKey] ?? "-";
    }
};

// CSV export
const exportCSV = () => {
    const cols = visibleColumns.value;
    const headers = cols.map((c) => c.label);
    const rows = props.projects.map((proj) =>
        cols
            .map((c) => {
                const val = formatCell(proj, c.key);
                // Escape quotes
                const s = String(val ?? "").replace(/"/g, '""');
                return `"${s}"`;
            })
            .join(",")
    );
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
                        <th v-for="col in visibleColumns" :key="col.key">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="project in projects" :key="project.id">
                        <td
                            v-for="col in visibleColumns"
                            :key="col.key"
                            :class="col.bold ? 'font-bold' : ''"
                        >
                            {{ formatCell(project, col.key) }}
                        </td>
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