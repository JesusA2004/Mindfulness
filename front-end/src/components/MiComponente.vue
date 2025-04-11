<template>
    <div class="mi-componente">
        <h1>{{ mensaje }}</h1>
    </div>
</template>

<script>
    export default {
        name: 'mi-componente',
        data() {
            return {
                mensaje: 'Hola, este es mi componente Vue!'
            };
        }
    };
</script>