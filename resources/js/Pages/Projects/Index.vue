<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { hasRole } from "@/util";
import { ref } from "vue";
import ProjectFullTable from "@/Components/ProjectFullTable.vue";

defineProps({
    projects: {
        type: Array,
        required: true,
    },
    auth: {
        type: Object,
        required: true,
    },
});

import { to_roman_numerical } from "@/util";
const deleteProject = (id) => {
    if (confirm("Are you sure you want to delete this project?")) {
        router.delete(route("projects.destroy", id));
    }
};

const showComparisonTable = ref(false);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Projects
                </h2>
                <Link
                    v-if="hasRole(['Admin', 'Super Admin'], auth.user)"
                    :href="route('projects.create')"
                    class="btn btn-primary"
                    >Create Project</Link
                >
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div
                            v-if="projects.length === 0"
                            class="text-center py-4"
                        >
                            No projects exist.
                        </div>
                        <div v-else>
                            <!-- Toggle to switch between default table and comparison table -->
                            <div class="flex justify-end mb-4">
                                <label class="cursor-pointer label">
                                    <span class="label-text mr-4">See comparison table</span>
                                    <input
                                        type="checkbox"
                                        v-model="showComparisonTable"
                                        class="toggle toggle-primary"
                                        @change="$event.target.blur()"
                                    />
                                </label>
                            </div>

                            <!-- Render comparison table when toggle is active -->
                            <ProjectFullTable
                                v-if="showComparisonTable"
                                :projects="projects"
                            />

                            <!-- Default condensed table -->
                            <div v-else class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Sponsor Name</th>
                                            <th>Project Name</th>
                                            <th>Contract Holder Country</th>
                                            <th>Project Manager</th>
                                            <th>Status</th>
                                            <th>Phase</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- row 1 -->
                                        <tr v-for="(project, index) in projects">
                                            <th>{{ index + 1 }}</th>
                                            <td>{{ project.sponsor_name }}</td>
                                            <td>{{ project.project_name }}</td>
                                            <td>
                                                {{
                                                    project.contract_holder_country
                                                }}
                                            </td>
                                            <td>{{ project.project_manager }}</td>
                                            <td>
                                                {{
                                                    project.status
                                                        ? "Active"
                                                        : "Inactive"
                                                }}
                                            </td>
                                            <td>
                                                {{
                                                    to_roman_numerical(
                                                        project.phase
                                                    )
                                                }}
                                            </td>
                                            <td class="flex gap-2">
                                                <Link
                                                    :href="
                                                        route(
                                                            'projects.show',
                                                            project.id
                                                        )
                                                    "
                                                    class="btn btn-sm btn-primary"
                                                    >View</Link
                                                >
                                                <button
                                                    @click="
                                                        deleteProject(project.id)
                                                    "
                                                    class="btn btn-sm btn-error"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
