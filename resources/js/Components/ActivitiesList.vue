<script setup>
import { ref } from "vue";
const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    locks: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["updated"]);

const updated_activity = (activity_id) => {
    const options = {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        url: route("activities.update", activity_id),
        data: {
            value: activities.value[activity_id],
        },
    };

    axios
        .request(options)
        .then((response) => {
            console.log(response.data);
            emit("updated", response.data);
        })
        .catch((error) => {
            console.error(error);
        });
};

const activities = ref({});
props.task.activities.forEach((activity) => {
    activities.value[activity.id] = activity.value;
});
</script>
<template>
    <td class="p-0" v-for="activity in task.activities" :key="activity.id">
        <div class="flex items-center justify-center gap-1 bg-gray-50 border border-gray-200 rounded-md px-1 py-0.5 mx-0.5 min-w-[38px]">
            <input
                type="text"
                v-model="activities[activity.id]"
                :disabled="locks.find((lock) => lock.date === activity.date).is_locked"
                class="w-8 p-0 text-center text-xs border-0 bg-transparent focus:bg-white disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition"
                @change="updated_activity(activity.id)"
                :title="locks.find((lock) => lock.date === activity.date).is_locked ? 'Locked for Actuals' : 'Editable (Forecast)'"
            />
            <span v-if="locks.find((lock) => lock.date === activity.date).is_locked" class="text-gray-300 text-xs ml-1" style="font-size: 0.8em;" title="Locked">
                <i class="fa fa-lock"></i>
            </span>
        </div>
    </td>
</template>
