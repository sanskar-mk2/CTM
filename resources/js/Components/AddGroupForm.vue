<script setup>
import { useForm } from "@inertiajs/vue3";
import CustomInput from "@/Components/CustomInput.vue";

const emit = defineEmits(["refresh"]);

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const form = useForm({
    name: "",
    project_id: props.projectId,
});

const submit = () => {
    form.post(route("groups.store"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            emit("refresh");
        },
    });
};
</script>

<template>
    <form class="flex items-center flex-col mb-4" @submit.prevent="submit">
        <CustomInput
            _id="name"
            _label="Group Name"
            v-model="form.name"
            :error="form.errors.name"
        />
        <input type="hidden" name="project_id" v-model="form.project_id" />
        <input
            type="submit"
            class="btn btn-primary max-w-xs w-full"
            value="Add Group"
        />
    </form>
</template>
