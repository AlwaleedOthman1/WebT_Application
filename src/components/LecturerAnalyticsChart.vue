/* global defineProps */
<template>
  <div>
    <Bar v-if="distributionData" :data="distributionData" :options="distributionOptions" />
  </div>
</template>

<script setup>
import { Bar } from 'vue-chartjs'
import { computed } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

const props = defineProps({
  distribution: Array,
  componentLabels: Array,
  componentAverages: Array
})

// Register Chart.js components to avoid scale registration errors
Chart.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const distributionData = computed(() => ({
  labels: props.distribution.map((_, i) => `Student ${i + 1}`),
  datasets: [
    {
      label: 'Total Marks',
      backgroundColor: '#42b983',
      data: props.distribution
    }
  ]
}))

const distributionOptions = {
  responsive: true,
  plugins: {
    legend: { position: 'top' },
    title: { display: true, text: 'Class Mark Distribution' }
  },
  scales: { y: { beginAtZero: true } }
}
</script> 