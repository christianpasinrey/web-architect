<script setup lang="ts">
    import {ref, defineAsyncComponent} from 'vue';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { usePage } from '@inertiajs/vue3';
    import type { Model, FieldType } from '@/types/model';

    const creatingModel = ref(false);
    const editingModel = ref(false);
    const viewingModel = ref<Model | null>(null);

    const { props } = usePage();

    const models = ref<Model[]>(Array.isArray(props.models) ? props.models as Model[] : []);
    const fieldTypes = ref<FieldType[]>(Array.isArray(props.fieldTypes) ? props.fieldTypes as FieldType[] : []);

    const Index = defineAsyncComponent(() => import('./Contexts/Index.vue'));
    const Create = defineAsyncComponent(() => import('./Contexts/Create.vue'));
    const Show = defineAsyncComponent(() => import('./Contexts/Show.vue'));

    console.log('Models:', models.value);
    console.log('Field Types:', fieldTypes.value);

    const handleCreate = () => {
        creatingModel.value = true;
        editingModel.value = false;
        viewingModel.value = null;
    };

    const handleView = (model: Model) => {
        viewingModel.value = model;
        creatingModel.value = false;
        editingModel.value = false;
    };

    const handleCancel = () => {
        creatingModel.value = false;
        editingModel.value = false;
        viewingModel.value = null;
    };
</script>
<template>
    <AppLayout>
        <Index
            v-if="!creatingModel && !editingModel && !viewingModel"
            :models="models"
            :fieldTypes="fieldTypes"
            @create="handleCreate"
            @view="handleView"
        />
        <Create
            v-if="creatingModel"
            :fieldTypes="fieldTypes"
            @cancel="handleCancel"
        />
        <Show
            v-if="viewingModel"
            :model="viewingModel"
            :fieldTypes="fieldTypes"
            @back="handleCancel"
        />
    </AppLayout>
</template>
