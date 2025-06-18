<script setup>
import TasksList from "./TasksList.vue";
import _ from "lodash";
import { ref, watch } from "vue";
import dayjs from "dayjs";

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    showDates: {
        type: Boolean,
        required: true,
    },
    locks: {
        type: Array,
        required: true,
    },
    min: {
        type: String,
        required: true,
    },
    max: {
        type: String,
        required: true,
    },
    auth: {
        type: Object,
        required: true,
    },
    months: {
        type: Array,
        required: false,
        default: () => [],
    },
});

const tasks = ref(props.group.tasks);
watch(
    () => props.group.tasks,
    (_) => {
        tasks.value = props.group.tasks;
    }
);

// Helper functions for group totals
const isLocked = (date) => {
    const lockEntry = props.locks.find((lock) =>
        dayjs(lock.date).isSame(dayjs(date), "month")
    );
    return lockEntry ? lockEntry.is_locked : false;
};
const sumActual = (activities) => {
    return _.sumBy(activities, (activity) =>
        isLocked(activity.date) ? activity.value : 0
    );
};
const sumTotal = (activities) => {
    return _.sumBy(activities, "value");
};
</script>

<template>
    <tr class="bg-blue-200 border-t border-blue-200">
        <th
            colspan="100%"
            class="text-nowrap py-2 px-2 font-semibold text-blue-900 text-base text-start"
        >
            {{ group.name }}
        </th>
    </tr>
    <TasksList
        :auth="auth"
        :tasks="tasks"
        :locks="locks"
        :min="min"
        :max="max"
        :show-dates="showDates"
    />
    <tr class="bg-blue-50 border-b border-blue-200">
        <th colspan="6" class="text-start font-bold text-blue-800 py-2 pr-4">
            {{ group.name }} Total
        </th>
        <th class="font-extrabold text-green-700">
            {{
                Intl.NumberFormat("en-US").format(
                    _.sumBy(group.tasks, (task) => task.price * task.quantity)
                )
            }}
        </th>
        <td v-for="month in months" :key="month">
            {{
                _.sumBy(group.tasks, (task) =>
                    _.sumBy(
                        task.activities.filter((a) => dayjs(a.date).isSame(dayjs(month), "month")),
                        "value"
                    )
                )
            }}
        </td>
        <td>
            {{ _.sumBy(group.tasks, (task) => sumActual(task.activities)) }}
        </td>
        <td>
            {{ _.sumBy(group.tasks, (task) => sumTotal(task.activities)) }}
        </td>
        <td>
            {{
                Intl.NumberFormat("en-US").format(
                    _.sumBy(group.tasks, (task) => sumActual(task.activities) * task.price)
                )
            }}
        </td>
        <td></td>
    </tr>
    <tr class="h-2"></tr>
</template>
