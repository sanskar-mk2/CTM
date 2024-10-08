<script setup>
import GroupItem from "./GroupItem.vue";
import dayjs from "dayjs";
import { ref, defineProps } from "vue";
import { hasRole } from "@/util";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    auth: {
        type: Object,
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
</script>

<template>
    <div class="overflow-x-auto">
        <table class="table table-pin-rows table-pin-cols">
            <thead class="text-center">
                <tr>
                    <th>Tasks</th>
                    <th>UNIT</th>
                    <th v-if="hasRole(['Admin', 'Super Admin'], auth.user)">Start Date</th>
                    <th v-if="hasRole(['Admin', 'Super Admin'], auth.user)">End Date</th>
                    <th>No. of Units</th>
                    <th>Unit Price</th>
                    <th>Total Task Value</th>
                    <th
                        class="p-0"
                        v-for="month in project.months"
                        :key="month"
                    >
                        {{ dayjs(month).format("MMM-YY") }}
                    </th>
                    <th>Units Done</th>
                    <th>Units incl Forecast</th>
                    <th>Amount Done</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th v-if="hasRole(['Admin', 'Super Admin'], auth.user)"></th>
                    <th v-if="hasRole(['Admin', 'Super Admin'], auth.user)"></th>
                    <td
                        width="56px"
                        class="px-0.5 text-xs"
                        v-for="(lock, index) in locks"
                        :key="lock.date"
                        :class="{
                            'tracking-[2px]': locks[index].is_locked,
                        }"
                    >
                        <span>{{ locks[index].is_locked ? "Actual" : "Forecast" }}</span>
                        <br>
                        <input
                            class="checkbox checkbox-primary"
                            type="checkbox"
                            v-model="locks[index].is_locked"
                            @change="handleLockChange(lock)"
                        />
                    </td>
                </tr>
                <GroupItem
                :auth="auth"
                    v-for="group in project.groups"
                    :key="group.id"
                    :group="group"
                    :locks="locks"
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
    </div>
</template>
