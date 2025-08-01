<script setup lang="ts">
    import {ref, defineAsyncComponent} from 'vue';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { usePage } from '@inertiajs/vue3';
    import type { Model, FieldType } from '@/types/model';
    const creatingModel = ref(false);
    const editingModel = ref(false);
    const { props } = usePage();

    const models = ref<Model[]>(Array.isArray(props.models) ? props.models as Model[] : []);
    const fieldTypes = ref<FieldType[]>(Array.isArray(props.fieldTypes) ? props.fieldTypes as FieldType[] : []);

    const Index = defineAsyncComponent(() => import('./Contexts/Index.vue'));
    const Create = defineAsyncComponent(() => import('./Contexts/Create.vue'));

    console.log('Models:', models.value);
    console.log('Field Types:', fieldTypes.value);
</script>
<template>
    <AppLayout>
        <Index v-if="!creatingModel && !editingModel" :models="models" :fieldTypes="fieldTypes" @create="creatingModel = true" />
        <Create v-if="creatingModel" :fieldTypes="fieldTypes" @cancel="creatingModel = false" />
    </AppLayout>
</template>
