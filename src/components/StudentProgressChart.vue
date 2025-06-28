<template>
    <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
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
  
  // Register Chart.js components to avoid scale registration errors
  Chart.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)
  
  // eslint-disable-next-line no-undef
  const props = defineProps({
    labels: Array,
    data: Array,
    maxMarks: Array
  })
  
  const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
      {
        label: 'Your Marks',
        backgroundColor: '#42b983',
        data: props.data
      },
      {
        label: 'Max Marks',
        backgroundColor: '#e0e0e0',
        data: props.maxMarks
      }
    ]
  }))
  
  const chartOptions = {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: { display: true, text: 'Assessment Progress' }
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
  </script>