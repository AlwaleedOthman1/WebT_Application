<template>
  <div>
    <h2>Advisor Dashboard</h2>
    <h3>At-Risk Students</h3>
    <ul>
      <li v-for="s in atRisk" :key="s.student.id" style="color: red;">
        {{ s.student.full_name }} ({{ s.student.matric_number }}) - Course: {{ s.course_id }}, Total: {{ s.total }}
      </li>
    </ul>
    <h3>Your Advisees</h3>
    <ul>
      <li v-for="a in advisees" :key="a.id">
        <button @click="selectAdvisee(a)">{{ a.full_name }} ({{ a.matric_number }})</button>
      </li>
    </ul>
    <div v-if="selectedAdvisee">
      <h3>Advisee Report: {{ selectedAdvisee.full_name }}</h3>
      <ul>
        <li v-for="m in adviseeMarks" :key="m.course.course_id">
          <b>{{ m.course.course_code }}:</b>
          <ul>
            <li v-for="cm in m.component_marks" :key="cm.component_name">
              {{ cm.component_name }}: {{ cm.marks_obtained ?? 'N/A' }} / {{ cm.max_marks }} (Weight: {{ cm.weight }})
            </li>
            <li>Final Exam: {{ m.final_exam_mark ?? 'N/A' }}</li>
          </ul>
        </li>
      </ul>
      <h4>Record Meeting Notes</h4>
      <textarea v-model="meetingNote" placeholder="Enter meeting notes..."></textarea>
      <button @click="saveMeetingNote">Save Note</button>
      <div v-if="meetingMsg">{{ meetingMsg }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const advisor = ref(JSON.parse(localStorage.getItem('advisor')))
const atRisk = ref([])
const advisees = ref([])
const selectedAdvisee = ref(null)
const adviseeMarks = ref([])
const meetingNote = ref('')
const meetingMsg = ref('')

const fetchAtRisk = async () => {
  const res = await api.get(`/advisors/${advisor.value.id}/advisees/at-risk`)
  atRisk.value = res.data.at_risk
}
const fetchAdvisees = async () => {
  const res = await api.get(`/advisors/${advisor.value.id}/advisees`)
  advisees.value = res.data.advisees
}
const selectAdvisee = async (a) => {
  selectedAdvisee.value = a
  const res = await api.get(`/advisors/${advisor.value.id}/advisees/${a.id}/marks`)
  adviseeMarks.value = res.data.marks
  // Optionally fetch last meeting note
}
const saveMeetingNote = async () => {
  try {
    await api.post(`/advisors/${advisor.value.id}/advisees/${selectedAdvisee.value.id}/meeting-notes`, {
      note: meetingNote.value
    })
    meetingMsg.value = 'Note saved!'
    meetingNote.value = ''
  } catch (e) {
    meetingMsg.value = 'Failed to save note.'
  }
}
onMounted(() => {
  fetchAtRisk()
  fetchAdvisees()
})
</script> 