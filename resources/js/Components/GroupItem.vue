<script setup>
import TasksList from "./TasksList.vue";
import _ from "lodash";
import { ref, watch } from "vue";

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
});

const tasks = ref(props.group.tasks);
watch(
    () => props.group.tasks,
    (_) => {
        tasks.value = props.group.tasks;
    }
);
</script>

<template>
    <tr class="bg-blue-50 border-t border-blue-200">
        <th class="text-nowrap py-2 px-2 font-semibold text-blue-900 text-base">
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
    <tr class="bg-blue-100 border-b border-blue-200">
        <th colspan="6" class="text-right font-bold text-blue-800 py-2 pr-4">{{ group.name }} Total</th>
        <th class="font-extrabold text-green-700">
            {{
                Intl.NumberFormat("en-US").format(
                    _.sumBy(group.tasks, (task) => task.price * task.quantity)
                )
            }}
        </th>
    </tr>
    <tr class="h-2"></tr>
</template>
