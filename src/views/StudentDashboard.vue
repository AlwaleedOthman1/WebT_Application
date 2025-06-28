<template>
  <div class="container" style="max-width: 1400px; padding: 1.5em 2.5em;">
    <!-- Top Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5em;">
      <h2 style="margin: 0;">Welcome, {{ student.full_name }}</h2>
      <button @click="logout" class="btn">Logout</button>
    </div>

    <!-- Summary Cards -->
    <div style="display: flex; gap: 2em; margin-bottom: 1.5em; flex-wrap: nowrap;">
      <div class="card" style="flex: 1 1 0; min-width: 0; background: #e3f2fd;">
        <div style="font-size: 2em; font-weight: 700;">{{ courses.length }}</div>
        <div>Total Courses</div>
      </div>
      <div class="card" style="flex: 1 1 0; min-width: 0; background: #fffde7;">
        <div style="font-size: 2em; font-weight: 700;">--</div>
        <div>Attendance</div>
      </div>
      <div class="card" style="flex: 1 1 0; min-width: 0; background: #e8f5e9;">
        <div style="font-size: 2em; font-weight: 700;">--</div>
        <div>Notifications</div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 2em; align-items: start;">
      <!-- Left Column -->
      <div>
        <div class="card">
          <h3>Your Courses</h3>
          <ul>
            <li v-for="course in courses" :key="course.id">
              <button @click="selectCourse(course.id)" class="btn">
                {{ course.course_code }} - {{ course.course_name }}
              </button>
            </li>
          </ul>
        </div>
        <div v-if="dashboard" class="card">
          <h3>Assessment Breakdown</h3>
          <ul>
            <li v-for="cm in dashboard.component_marks" :key="cm.component_name">
              {{ cm.component_name }}: {{ isFinite(+cm.marks_obtained) ? (+cm.marks_obtained).toFixed(2) : 'N/A' }} / {{ isFinite(+cm.max_marks) ? (+cm.max_marks).toFixed(2) : 'N/A' }} (Weight: {{ isFinite(+cm.weight) ? (+cm.weight).toFixed(2) : 'N/A' }})
            </li>
          </ul>
          <p><b>Final Exam:</b> {{ isFinite(+dashboard.final_exam_mark) ? (+dashboard.final_exam_mark).toFixed(2) : 'N/A' }}</p>
          <p><b>Total:</b> {{ isFinite(+dashboard.total) ? (+dashboard.total).toFixed(2) : 'N/A' }}</p>
        </div>
        <div v-if="dashboard" class="card">
          <h3>Your Mark vs Total</h3>
          <Bar :data="totalVsMaxChartData" :options="totalVsMaxChartOptions" />
        </div>
        <div v-if="dashboard" class="card">
          <h3>Compare with Coursemates</h3>
          <button @click="fetchComparison" class="btn">Show Comparison</button>
          <div v-if="comparisonResults.length">
            <ul>
              <li v-for="r in comparisonResults" :key="r.anon_id">
                <span :style="r.anon_id === myAnonId ? 'font-weight:bold; color:green' : ''">
                  {{ r.anon_id === myAnonId ? 'You' : 'Anonymous' }}: {{ r.total }}
                </span>
              </li>
            </ul>
          </div>
        </div>
        <div v-if="dashboard" class="card">
          <h3>Your Ranking/Position</h3>
          <button @click="fetchRank" class="btn">Show My Rank</button>
          <div v-if="myRank !== null">
            <p>Your rank: {{ myRank }}</p>
            <p>Your percentile: {{ myPercentile.toFixed(2) }}%</p>
          </div>
        </div>
        <div v-if="dashboard" class="card">
          <h3>Class Average per Assessment</h3>
          <Bar v-if="componentAvgChartData" :data="componentAvgChartData" :options="classAvgOptions" />
          <ul v-if="componentAvgChartData">
            <li v-for="(avg, idx) in componentAvgChartData.datasets[0].data" :key="componentAvgChartData.labels[idx]">
              {{ componentAvgChartData.labels[idx] }}: {{ isFinite(+avg) ? (+avg).toFixed(2) : 'N/A' }}
            </li>
          </ul>
        </div>
        <div v-if="dashboard" class="card">
          <h3>Performance Trend</h3>
          <Line v-if="trendData" :data="trendData" :options="trendOptions" />
        </div>
      </div>
      <!-- Right Column -->
      <div>
        <div class="card">
          <h3>Notifications</h3>
          <ul style="padding-left: 0;">
            <li v-for="n in notifications" :key="n.id"
                @click="markAsRead(n.id)"
                @mouseover="onNotifHover($event)"
                @mouseleave="onNotifLeave($event)"
                style="cursor:pointer; list-style: none; padding: 0.5em 0.8em; border-radius: 6px; transition: background 0.2s; margin-bottom: 0.3em;">
              • {{ n.message }}
            </li>
          </ul>
        </div>
        <div class="card">
          <form @submit.prevent="submitRemark">
            <h3>Request a Remark</h3>
            <select v-model="remarkComponentId">
              <option :value="null">Final Exam</option>
              <option v-for="cm in dashboard?.component_marks || []" :key="cm.component_name" :value="cm.id">
                {{ cm.component_name }}
              </option>
            </select>
            <input v-model="remarkJustification" placeholder="Justification" required />
            <button type="submit" class="btn">Submit Remark</button>
            <div v-if="remarkMsg">{{ remarkMsg }}</div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import { Bar, Line } from 'vue-chartjs'
import {
  Chart,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
Chart.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend)

const student = ref(JSON.parse(localStorage.getItem('student')))
const courses = ref([])
const selectedCourse = ref(null)
const dashboard = ref(null)
const notifications = ref([])
const remarkComponentId = ref(null)
const remarkJustification = ref('')
const remarkMsg = ref('')
const comparisonResults = ref([])
const myAnonId = ref('')
const myRank = ref(null)
const myPercentile = ref(0)
const courseStudents = ref([])
const classAvgOptions = {
  responsive: true,
  plugins: {
    legend: { position: 'top' },
    title: { display: true, text: 'Class Average per Component' }
  },
  scales: { y: { beginAtZero: true } }
}
const trendData = ref(null)
const trendOptions = {
  responsive: true,
  plugins: {
    legend: { position: 'top' },
    title: { display: true, text: 'Performance Trend' }
  },
  scales: { y: { beginAtZero: true } }
}
const componentAvgChartData = ref(null)

const totalVsMaxChartData = computed(() => {
  if (!dashboard.value) return { labels: [], datasets: [] };
  const maxTotal = dashboard.value.component_marks.reduce((sum, cm) => sum + (+cm.max_marks || 0), 0) + (+dashboard.value.final_exam_max || 0);
  return {
    labels: ['Your Total', 'Max Possible'],
    datasets: [
      {
        label: 'Marks',
        backgroundColor: ['#2563eb', '#e3e3e3'],
        data: [
          isFinite(+dashboard.value.total) ? +dashboard.value.total : 0,
          isFinite(maxTotal) ? maxTotal : 0
        ]
      }
    ]
  };
});
const totalVsMaxChartOptions = {
  responsive: true,
  plugins: {
    legend: { display: false },
    title: { display: false }
  },
  scales: { y: { beginAtZero: true } }
};

onMounted(async () => {
  await fetchCourses()
  await fetchNotifications()
})

const fetchCourses = async () => {
  const res = await api.get(`/students/${student.value.id}/courses`)
  courses.value = res.data.courses
  if (courses.value.length) {
    selectedCourse.value = courses.value[0].id
    fetchDashboard()
  }
}

const selectCourse = (courseId) => {
  selectedCourse.value = courseId
  fetchDashboard()
}

const fetchDashboard = async () => {
  dashboard.value = null
  if (!selectedCourse.value) return
  const res = await api.get(`/students/${student.value.id}/courses/${selectedCourse.value}/dashboard`)
  dashboard.value = res.data
  // For chart
  if (dashboard.value.class_avg_labels && dashboard.value.class_avg_averages) {
    componentAvgChartData.value = {
      labels: dashboard.value.class_avg_labels,
      datasets: [
        {
          label: 'Component Average',
          backgroundColor: '#ffa726',
          data: dashboard.value.class_avg_averages
        }
      ]
    }
  }
}

const fetchNotifications = async () => {
  const res = await api.get(`/students/${student.value.id}/notifications`)
  notifications.value = res.data.notifications
}

const markAsRead = async (id) => {
  await api.post(`/notifications/${id}/read`)
  notifications.value = notifications.value.filter(n => n.id !== id)
}

const submitRemark = async () => {
  try {
    await api.post('/remark-requests', {
      student_id: student.value.id,
      course_id: selectedCourse.value,
      component_id: remarkComponentId.value,
      justification: remarkJustification.value
    })
    remarkMsg.value = 'Remark request submitted!'
  } catch (e) {
    remarkMsg.value = 'Failed to submit remark.'
  }
}

const fetchComparison = async () => {
  if (!selectedCourse.value) return
  const res = await api.get(`/courses/${selectedCourse.value}/marks/compare`)
  comparisonResults.value = res.data.results
  // Fetch students for the course to map anon_id to names
  const studentsRes = await api.get(`/courses/${selectedCourse.value}/students`)
  courseStudents.value = studentsRes.data.students
  // Find this student's anon_id (backend uses md5(student_id).slice(0,8))
  myAnonId.value = md5(String(student.value.id)).slice(0, 8)
}

// Minimal md5 implementation for anon_id matching (not cryptographically secure, just for matching backend logic)
function md5(str) {
  let hash = 0, i, chr
  if (str.length === 0) return '00000000'
  for (i = 0; i < str.length; i++) {
    chr = str.charCodeAt(i)
    hash = ((hash << 5) - hash) + chr
    hash |= 0
  }
  return ('00000000' + (Math.abs(hash).toString(16))).slice(-8)
}

const fetchRank = async () => {
  if (!selectedCourse.value) return
  const res = await api.get(`/students/${student.value.id}/courses/${selectedCourse.value}/rank`)
  myRank.value = res.data.rank
  myPercentile.value = res.data.percentile
}

const logout = () => {
  localStorage.removeItem('student')
  window.location.href = '/student/login'
}

function onNotifHover(event) {
  event.target.style.background = '#f1f5fb';
}

function onNotifLeave(event) {
  event.target.style.background = '';
}
</script>

<style scoped>
.error { color: red; margin-top: 1rem; }
</style>