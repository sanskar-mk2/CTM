<script setup>
import GroupItem from "./GroupItem.vue";
import dayjs from "dayjs";
import { ref, computed } from "vue";
import { hasRole } from "@/util";
import Modal from "./Modal.vue";
import _ from "lodash";
import "@fortawesome/fontawesome-free/css/all.min.css";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    auth: {
        type: Object,
        required: true,
    },
    showDates: {
        type: Boolean,
        required: true,
    },
});

const locks = ref(props.project.locks);

const handleLockChange = (lock) => {
    const options = {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        url: route("locks.update", lock.id),
        data: {
            is_locked: lock.is_locked,
        },
    };

    axios
        .request(options)
        .then((response) => {
            console.log(response.data);
        })
        .catch((error) => {
            console.error(error);
        });
};

const showChangeOrderModal = ref(false);
const changeOrderTasks = computed(() => {
    // Flatten all tasks from all groups, including group name
    const tasks = props.project.groups.flatMap((group) =>
        group.tasks.map((task) => ({ ...task, groupName: group.name }))
    );
    // Helper to get sumActual for a task
    const isLocked = (date) => {
        const lockEntry = props.project.locks.find((lock) =>
            dayjs(lock.date).isSame(dayjs(date), "month")
        );
        return lockEntry ? lockEntry.is_locked : false;
    };
    const sumActual = (activities) => {
        return _.sumBy(activities, (activity) =>
            isLocked(activity.date) ? activity.value : 0
        );
    };
    return tasks
        .map((task) => {
            const actual = sumActual(task.activities);
            return {
                id: task.id,
                name: task.name,
                groupName: task.groupName,
                quantity: task.quantity,
                actual,
                exceeded: actual - task.quantity,
                unit: task.unit,
            };
        })
        .filter((task) => task.exceeded > 0);
});

function exportChangeOrdersToExcel() {
    // Prepare data for export
    const rows = [
        [
            "Group",
            "Task Name",
            "Unit",
            "Allowed Quantity",
            "Actual Units Done",
            "Exceeded By",
        ],
        ...changeOrderTasks.value.map((task) => [
            task.groupName,
            task.name,
            task.unit,
            task.quantity,
            task.actual,
            task.exceeded,
        ]),
    ];
    // Convert to CSV string
    const csvContent = rows.map((e) => e.join(",")).join("\n");
    // Create a blob and trigger download
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "change_order_activities.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div class="overflow-x-auto p-4 bg-white rounded-lg shadow-md">
        <table class="table table-pin-rows table-pin-cols min-w-full text-sm">
            <thead class="text-center bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th class="py-2">Tasks</th>
                    <th>UNIT</th>
                    <th
                        v-if="
                            hasRole(['Admin', 'Super Admin'], auth.user) &&
                            showDates
                        "
                    >
                        <span>Start Date</span>
                    </th>
                    <th
                        v-if="
                            hasRole(['Admin', 'Super Admin'], auth.user) &&
                            showDates
                        "
                    >
                        <span>End Date</span>
                    </th>
                    <th>No. of Units</th>
                    <th>Unit Price</th>
                    <th>Total Task Value</th>
                    <th
                        class="p-0"
                        v-for="month in project.months"
                        :key="month"
                    >
                        <span class="block text-xs font-semibold">{{
                            dayjs(month).format("MMM-YY")
                        }}</span>
                    </th>
                    <th>Units Done</th>
                    <th>Units incl Forecast</th>
                    <th>Amount Done</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <tr class="bg-gray-50">
                    <th class="bg-gray-50"></th>
                    <th class="bg-gray-50"></th>
                    <th v-if="showDates" class="bg-gray-50"></th>
                    <th v-if="showDates" class="bg-gray-50"></th>
                    <th class="bg-gray-50"></th>
                    <th
                        class="bg-gray-50"
                        v-if="hasRole(['Admin', 'Super Admin'], auth.user)"
                    ></th>
                    <th
                        class="bg-gray-50"
                        v-if="hasRole(['Admin', 'Super Admin'], auth.user)"
                    ></th>
                    <td
                        width="56px"
                        class="px-0.5 text-xs"
                        v-for="(lock, index) in locks"
                        :key="lock.date"
                        :class="{
                            'tracking-[2px]': locks[index].is_locked,
                        }"
                    >
                        <span
                            class="block text-[10px] font-medium"
                            :class="
                                locks[index].is_locked
                                    ? 'text-green-600'
                                    : 'text-gray-400'
                            "
                        >
                            {{ locks[index].is_locked ? "Actual" : "Forecast" }}
                        </span>
                        <br />
                        <input
                            class="checkbox checkbox-primary scale-90"
                            type="checkbox"
                            v-model="locks[index].is_locked"
                            @change="handleLockChange(lock)"
                            :title="
                                locks[index].is_locked
                                    ? 'Locked for Actuals'
                                    : 'Editable (Forecast)'
                            "
                        />
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>
                        <button
                            v-if="
                                changeOrderTasks.length > 0 &&
                                hasRole(
                                    ['Admin', 'Super Admin', 'Manager'],
                                    auth.user
                                )
                            "
                            class="btn btn-warning py-1 px-2 text-xs rounded-md flex items-center gap-1 shadow-sm hover:shadow-md transition"
                            @click="showChangeOrderModal = true"
                            title="See change order activity"
                        >
                            <i class="fa fa-exclamation-circle"></i>
                            See Change Orders
                        </button>
                    </td>
                </tr>
                <GroupItem
                    :auth="auth"
                    v-for="group in project.groups"
                    :key="group.id"
                    :group="group"
                    :locks="locks"
                    :show-dates="showDates"
                    :min="project.activity_start_date"
                    :max="
                        dayjs(project.activity_start_date)
                            .add(project.clinical_duration, 'month')
                            .subtract(1, 'day')
                            .format('YYYY-MM-DD')
                    "
                />
            </tbody>
        </table>
        <Modal
            :show="showChangeOrderModal"
            @close="showChangeOrderModal = false"
        >
            <div class="p-4">
                <h2 class="text-lg font-bold mb-2">Change Order Activities</h2>
                <button
                    class="btn btn-success btn-xs mb-2 rounded-md flex items-center gap-1"
                    @click="exportChangeOrdersToExcel"
                >
                    <i class="fa fa-file-excel-o"></i>
                    Export to Excel
                </button>
                <table class="table w-full text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Group</th>
                            <th>Task Name</th>
                            <th>Unit</th>
                            <th>Allowed Quantity</th>
                            <th>Actual Units Done</th>
                            <th>Exceeded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="task in changeOrderTasks"
                            :key="task.id"
                            class="hover:bg-red-50 transition"
                        >
                            <td>{{ task.groupName }}</td>
                            <td>{{ task.name }}</td>
                            <td>{{ task.unit }}</td>
                            <td>{{ task.quantity }}</td>
                            <td>{{ task.actual }}</td>
                            <td class="text-red-600 font-bold">
                                +{{ task.exceeded }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 text-right">
                    <button
                        class="btn btn-primary btn-sm rounded-md"
                        @click="showChangeOrderModal = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
