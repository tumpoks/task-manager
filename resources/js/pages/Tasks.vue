<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: string;
    due_date: string | null;
}

const tasks = ref<Task[]>([]);
const loading = ref(true);
const error = ref('');
const success = ref('');
const submitting = ref(false);

const newTask = ref({
    title: '',
    description: '',
    status: 'Pending',
    due_date: '',
});

const editingTask = ref<Task | null>(null);

const fetchTasks = async () => {
    try {
        const response = await fetch('/api/tasks');

        if (!response.ok) {
            throw new Error('Failed to fetch tasks.');
        }

        tasks.value = await response.json();
    } catch (err) {
        error.value = 'Unable to load tasks.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const addTask = async () => {
    error.value = '';
    success.value = '';
    submitting.value = true;

    try {
        const response = await fetch('/api/tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(newTask.value),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                error.value = Object.values(data.errors)
                    .flat()
                    .join(' ');
            } else {
                error.value = data.message || 'Unable to create task.';
            }

            return;
        }

        tasks.value.unshift(data);

        newTask.value = {
            title: '',
            description: '',
            status: 'Pending',
            due_date: '',
        };

        success.value = 'Task created successfully.';
    } catch (err) {
        error.value = 'Unable to create task.';
        console.error(err);
    } finally {
        submitting.value = false;
    }
};

const startEditing = (task: Task) => {
    error.value = '';
    success.value = '';

    editingTask.value = {
        ...task,
        due_date: task.due_date
            ? task.due_date.substring(0, 10)
            : null,
    };
};

const cancelEditing = () => {
    editingTask.value = null;
    error.value = '';
};

const updateTask = async () => {
    if (!editingTask.value) {
        return;
    }

    error.value = '';
    success.value = '';
    submitting.value = true;

    try {
        const response = await fetch(
            `/api/tasks/${editingTask.value.id}`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    title: editingTask.value.title,
                    description: editingTask.value.description,
                    status: editingTask.value.status,
                    due_date: editingTask.value.due_date,
                }),
            },
        );

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                error.value = Object.values(data.errors)
                    .flat()
                    .join(' ');
            } else {
                error.value = data.message || 'Unable to update task.';
            }

            return;
        }

        const index = tasks.value.findIndex(
            (task) => task.id === editingTask.value?.id,
        );

        if (index !== -1) {
            tasks.value[index] = data;
        }

        editingTask.value = null;
        success.value = 'Task updated successfully.';
    } catch (err) {
        error.value = 'Unable to update task.';
        console.error(err);
    } finally {
        submitting.value = false;
    }
};

const deleteTask = async (id: number) => {
    const confirmed = confirm(
        'Are you sure you want to delete this task?',
    );

    if (!confirmed) {
        return;
    }

    error.value = '';
    success.value = '';

    try {
        const response = await fetch(`/api/tasks/${id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = data.message || 'Unable to delete task.';
            return;
        }

        tasks.value = tasks.value.filter(
            (task) => task.id !== id,
        );

        success.value = 'Task deleted successfully.';
    } catch (err) {
        error.value = 'Unable to delete task.';
        console.error(err);
    }
};

onMounted(fetchTasks);
</script>

<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-4xl">
            <h1 class="mb-2 text-3xl font-bold text-gray-900">
                Task Manager
            </h1>

            <p class="mb-8 text-gray-600">
                Manage your tasks.
            </p>

            <!-- Success message -->
            <div
                v-if="success"
                class="mb-4 rounded-lg bg-green-100 p-4 text-green-700"
            >
                {{ success }}
            </div>

            <!-- Error message -->
            <div
                v-if="error"
                class="mb-4 rounded-lg bg-red-100 p-4 text-red-700"
            >
                {{ error }}
            </div>

            <!-- Add Task Form -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold text-gray-900">
                    Add New Task
                </h2>

                <form
                    @submit.prevent="addTask"
                    class="space-y-4"
                >
                    <div>
                        <label
                            for="title"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Title
                        </label>

                        <input
                            id="title"
                            v-model="newTask.title"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2"
                            placeholder="Enter task title"
                        />
                    </div>

                    <div>
                        <label
                            for="description"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Description
                        </label>

                        <textarea
                            id="description"
                            v-model="newTask.description"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2"
                            rows="3"
                            placeholder="Enter task description"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            for="status"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            v-model="newTask.status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2"
                        >
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="due_date"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Due Date
                        </label>

                        <input
                            id="due_date"
                            v-model="newTask.due_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded-lg bg-blue-600 px-5 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ submitting ? 'Saving...' : 'Add Task' }}
                    </button>
                </form>
            </div>

            <!-- Loading -->
            <div
                v-if="loading"
                class="rounded-lg bg-white p-6 shadow"
            >
                Loading tasks...
            </div>

            <!-- Tasks -->
            <div
                v-else
                class="space-y-4"
            >
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    class="rounded-lg bg-white p-6 shadow"
                >
                    <!-- Normal View -->
                    <div v-if="editingTask?.id !== task.id">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2
                                    class="text-xl font-semibold text-gray-900"
                                >
                                    {{ task.title }}
                                </h2>

                                <p
                                    v-if="task.description"
                                    class="mt-2 text-gray-600"
                                >
                                    {{ task.description }}
                                </p>
                            </div>

                            <span
                                class="rounded-full px-3 py-1 text-sm font-medium"
                                :class="
                                    task.status === 'Completed'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800'
                                "
                            >
                                {{ task.status }}
                            </span>
                        </div>

                        <div
                            v-if="task.due_date"
                            class="mt-4 text-sm text-gray-500"
                        >
                            Due:
                            {{ task.due_date.substring(0, 10) }}
                        </div>

                        <div class="mt-4 flex gap-2">
                            <button
                                @click="startEditing(task)"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Edit
                            </button>

                            <button
                                @click="deleteTask(task.id)"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div v-else-if="editingTask">
                        <h2
                            class="mb-4 text-xl font-semibold text-gray-900"
                        >
                            Edit Task
                        </h2>

                        <form
                            @submit.prevent="updateTask"
                            class="space-y-4"
                        >
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Title
                                </label>

                                <input
                                    v-model="editingTask.title"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Description
                                </label>

                                <textarea
                                    v-model="editingTask.description"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2"
                                    rows="3"
                                ></textarea>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Status
                                </label>

                                <select
                                    v-model="editingTask.status"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2"
                                >
                                    <option value="Pending">
                                        Pending
                                    </option>

                                    <option value="Completed">
                                        Completed
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Due Date
                                </label>

                                <input
                                    v-model="editingTask.due_date"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2"
                                />
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                                >
                                    {{
                                        submitting
                                            ? 'Saving...'
                                            : 'Save Changes'
                                    }}
                                </button>

                                <button
                                    type="button"
                                    @click="cancelEditing"
                                    class="rounded-lg bg-gray-500 px-4 py-2 text-sm font-medium text-white hover:bg-gray-600"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="tasks.length === 0"
                    class="rounded-lg bg-white p-6 text-center text-gray-500 shadow"
                >
                    No tasks found.
                </div>
            </div>
        </div>
    </div>
</template>