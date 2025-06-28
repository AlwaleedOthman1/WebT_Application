<template>
    <div>
      <h2>Welcome, {{ lecturer.full_name }}</h2>
      <button @click="logout">Logout</button>
      <h3>Add New Course</h3>
      <form @submit.prevent="addCourse">
        <input v-model="newCourseCode" placeholder="Course Code" required />
        <input v-model="newCourseName" placeholder="Course Name" required />
        <button type="submit">Add Course</button>
        <span v-if="courseMsg">{{ courseMsg }}</span>
      </form>
      <h3>Your Courses</h3>
      <ul>
        <li v-for="course in courses" :key="course.id">
          <template v-if="editCourseId === course.id">
            <input v-model="editCourseCode" placeholder="Course Code" required />
            <input v-model="editCourseName" placeholder="Course Name" required />
            <button @click="saveCourseEdit(course.id)">Save</button>
            <button @click="cancelEditCourse">Cancel</button>
          </template>
          <template v-else>
            <button @click="selectCourse(course.id)">
              {{ course.course_code }} - {{ course.course_name }}
            </button>
            <button @click="startEditCourse(course)">Edit</button>
            <button @click="deleteCourse(course.id)">Delete</button>
          </template>
        </li>
      </ul>
      <div v-if="selectedCourse">
        <h3>Assessment Components</h3>
        <ul>
          <li v-for="comp in components" :key="comp.id">
            <template v-if="editComponentId === comp.id">
              <input v-model="editComponentName" placeholder="Assessment Name" required />
              <input v-model.number="editComponentWeight" type="number" placeholder="Weight (%)" required style="width: 60px" />
              <input v-model.number="editComponentMax" type="number" placeholder="Max Marks" required style="width: 60px" />
              <button @click="saveComponentEdit(comp.id)">Save</button>
              <button @click="cancelEditComponent">Cancel</button>
            </template>
            <template v-else>
              {{ comp.component_name }} (Weight: {{ comp.weight }}, Max: {{ comp.max_marks }})
              <button @click="startEditComponent(comp)">Edit</button>
              <button @click="deleteComponent(comp.id)">Delete</button>
            </template>
          </li>
        </ul>
        <p><b>Total Weight:</b> {{ totalComponentWeight }}% (Assessments: 70% required, Final Exam: 30%)</p>
        <p v-if="totalComponentWeight !== 70" style="color: red;">Warning: The total weight for assessments must be exactly 70%.</p>
        <form @submit.prevent="addComponent">
          <input v-model="newComponentName" placeholder="Assessment Name" required />
          <input v-model.number="newComponentWeight" type="number" placeholder="Weight (%)" required :max="70 - totalComponentWeight" />
          <input v-model.number="newComponentMax" type="number" placeholder="Max Marks" required />
          <button type="submit" :disabled="totalComponentWeight >= 70">Add Assessment</button>
        </form>
        <div v-if="componentMsg">{{ componentMsg }}</div>
        <h3>Marks Entry</h3>
        <div v-if="students.length && components.length">
          <table border="1">
            <thead>
              <tr>
                <th>Matric Number</th>
                <th>Full Name</th>
                <th v-for="comp in components" :key="comp.id">{{ comp.component_name }}</th>
                <th>Final Exam</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.id">
                <td>{{ student.matric_number }}</td>
                <td>{{ student.full_name }}</td>
                <td v-for="comp in components" :key="comp.id">
                  <input
                    type="number"
                    :min="0"
                    :max="comp.max_marks"
                    :value="getMark(student.id, comp.id)"
                    @input="setMark(student.id, comp.id, $event.target.value)"
                    style="width: 60px"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    :value="getFinalExamMark(student.id)"
                    @input="setFinalExamMark(student.id, $event.target.value)"
                    style="width: 60px"
                  />
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="marksMsg">{{ marksMsg }}</div>
        </div>
        <div v-else>
          <p>No students or components found for this course.</p>
        </div>
        <button @click="exportCSV">Export Marks as CSV</button>
        <h3>Analytics</h3>
        <LecturerAnalyticsChart
          v-if="distribution && componentAverages"
          :distribution="distribution"
          :componentLabels="componentLabels"
          :componentAverages="componentAverages"
        />
        <h3>Enroll Student</h3>
        <form @submit.prevent="enrollStudent">
          <input v-model="enrollMatric" placeholder="Student Matric Number" required />
          <button type="submit">Enroll</button>
        </form>
        <div v-if="enrollMsg">{{ enrollMsg }}</div>
        <h4>Enrolled Students</h4>
        <ul>
          <li v-for="student in students" :key="student.id">
            {{ student.matric_number }} - {{ student.full_name }}
          </li>
        </ul>
        <h3>Manage Students</h3>
        <form @submit.prevent="addStudent">
          <input v-model="newStudentMatric" placeholder="Matric Number" required />
          <input v-model="newStudentName" placeholder="Full Name" required />
          <input v-model="newStudentEmail" placeholder="Email" required />
          <input v-model="newStudentPin" placeholder="PIN" required />
          <button type="submit">Add Student</button>
        </form>
        <div v-if="studentMsg">{{ studentMsg }}</div>
        <ul>
          <li v-for="student in allStudents" :key="student.id">
            <template v-if="editStudentId === student.id">
              <input v-model="editStudentMatric" />
              <input v-model="editStudentName" />
              <input v-model="editStudentEmail" />
              <input v-model="editStudentPin" />
              <button @click="saveStudentEdit(student.id)">Save</button>
              <button @click="cancelStudentEdit">Cancel</button>
            </template>
            <template v-else>
              {{ student.matric_number }} - {{ student.full_name }} ({{ student.email }})
              <button @click="startStudentEdit(student)">Edit</button>
              <button @click="deleteStudent(student.id)">Delete</button>
            </template>
          </li>
        </ul>
        <p>Selected Course ID: {{ selectedCourse }}</p>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, watch, computed } from 'vue'
  import api from '../api'
  import { useRouter } from 'vue-router'
  import LecturerAnalyticsChart from '../components/LecturerAnalyticsChart.vue'
  
  const router = useRouter()
  const lecturer = ref(JSON.parse(localStorage.getItem('lecturer')))
  const courses = ref([])
  const selectedCourse = ref(null)
  
  const components = ref([])
  const newComponentName = ref('')
  const newComponentWeight = ref('')
  const newComponentMax = ref('')
  const componentMsg = ref('')
  
  const students = ref([])
  const marks = ref({})
  const finalExamMarks = ref({})
  const marksMsg = ref('')
  
  const distribution = ref(null)
  const componentLabels = ref([])
  const componentAverages = ref([])
  
  const editComponentId = ref(null)
  const editComponentName = ref('')
  const editComponentWeight = ref('')
  const editComponentMax = ref('')
  
  const totalComponentWeight = computed(() => {
    return components.value.reduce((sum, comp) => sum + Number(comp.weight || 0), 0)
  })
  
  const enrollMatric = ref('')
  const enrollMsg = ref('')
  
  const allStudents = ref([])
  const newStudentMatric = ref('')
  const newStudentName = ref('')
  const newStudentEmail = ref('')
  const newStudentPin = ref('')
  const studentMsg = ref('')
  const editStudentId = ref(null)
  const editStudentMatric = ref('')
  const editStudentName = ref('')
  const editStudentEmail = ref('')
  const editStudentPin = ref('')
  
  const newCourseCode = ref('')
  const newCourseName = ref('')
  const courseMsg = ref('')
  const editCourseId = ref(null)
  const editCourseCode = ref('')
  const editCourseName = ref('')
  
  const fetchCourses = async () => {
    const res = await api.get('/courses')
    // Filter courses assigned to this lecturer
    courses.value = res.data.courses.filter(c => c.lecturer_id == lecturer.value.id)
    if (courses.value.length) {
      selectedCourse.value = courses.value[0].id
    }
  }
  
  const fetchComponents = async () => {
    if (!selectedCourse.value) return
    const res = await api.get(`/courses/${selectedCourse.value}/components`)
    components.value = res.data.components
  }
  
  const addComponent = async () => {
    if (totalComponentWeight.value + Number(newComponentWeight.value) > 70) {
      componentMsg.value = 'Total component weight cannot exceed 70%.'
      return
    }
    try {
      await api.post(`/courses/${selectedCourse.value}/components`, {
        component_name: newComponentName.value,
        weight: newComponentWeight.value,
        max_marks: newComponentMax.value
      })
      componentMsg.value = 'Component added!'
      newComponentName.value = ''
      newComponentWeight.value = ''
      newComponentMax.value = ''
      fetchComponents()
    } catch (e) {
      componentMsg.value = 'Failed to add component.'
    }
  }
  
  const deleteComponent = async (id) => {
    try {
      await api.delete(`/components/${id}`)
      componentMsg.value = 'Component deleted!'
      fetchComponents()
    } catch (e) {
      componentMsg.value = 'Failed to delete component.'
    }
  }
  
  const fetchStudents = async () => {
    if (!selectedCourse.value) return
    const res = await api.get(`/courses/${selectedCourse.value}/students`)
    students.value = res.data.students
  }
  
  const fetchMarks = async () => {
    if (!selectedCourse.value) return
    const newMarks = {}
    const newFinalExamMarks = {}
    for (const student of students.value) {
      newMarks[student.id] = {}
      for (const comp of components.value) {
        const res = await api.get(`/students/${student.id}/courses/${selectedCourse.value}/marks`)
        const cm = res.data.component_marks.find(m => m.component_name === comp.component_name)
        newMarks[student.id][comp.id] = cm?.marks_obtained ?? ''
        newFinalExamMarks[student.id] = res.data.final_exam_mark ?? ''
      }
    }
    marks.value = newMarks
    finalExamMarks.value = newFinalExamMarks
  }
  
  const getMark = (studentId, compId) => {
    return marks.value[studentId] && marks.value[studentId][compId] !== undefined
      ? marks.value[studentId][compId]
      : ''
  }
  
  const setMark = (studentId, compId, value) => {
    if (!marks.value[studentId]) marks.value[studentId] = {}
    marks.value[studentId][compId] = value
    saveMark(studentId, compId, value)
  }
  
  const getFinalExamMark = (studentId) => {
    return finalExamMarks.value[studentId] !== undefined
      ? finalExamMarks.value[studentId]
      : ''
  }
  
  const setFinalExamMark = (studentId, value) => {
    finalExamMarks.value[studentId] = value
    saveFinalExamMark(studentId, value)
  }
  
  const saveMark = async (studentId, componentId, value) => {
    try {
      await api.post('/marks', {
        student_id: studentId,
        component_id: componentId,
        marks_obtained: value,
        lecturer_id: lecturer.value.id
      })
      marksMsg.value = 'Mark saved!'
    } catch (e) {
      marksMsg.value = 'Failed to save mark.'
    }
  }
  
  const saveFinalExamMark = async (studentId, value) => {
    try {
      await api.post('/final-exam-marks', {
        student_id: studentId,
        course_id: selectedCourse.value,
        marks_obtained: value,
        lecturer_id: lecturer.value.id
      })
      marksMsg.value = 'Final exam mark saved!'
    } catch (e) {
      marksMsg.value = 'Failed to save final exam mark.'
    }
  }
  
  const fetchAnalytics = async () => {
    if (!selectedCourse.value) return
    const distRes = await api.get(`/courses/${selectedCourse.value}/distribution`)
    distribution.value = distRes.data.totals
    const avgRes = await api.get(`/courses/${selectedCourse.value}/component-averages`)
    componentLabels.value = avgRes.data.labels
    componentAverages.value = avgRes.data.averages
  }
  
  const exportCSV = () => {
    if (!selectedCourse.value) return
    window.open(`http://localhost:8080/api/courses/${selectedCourse.value}/export-marks`, '_blank')
  }
  
  const selectCourse = async (courseId) => {
    selectedCourse.value = courseId
    await fetchComponents()
    await fetchStudents()
    await fetchMarks()
    await fetchAnalytics()
  }
  
  const logout = () => {
    localStorage.removeItem('lecturer')
    router.push('/lecturer/login')
  }
  
  const startEditComponent = (comp) => {
    editComponentId.value = comp.id
    editComponentName.value = comp.component_name
    editComponentWeight.value = comp.weight
    editComponentMax.value = comp.max_marks
  }
  
  const cancelEditComponent = () => {
    editComponentId.value = null
    editComponentName.value = ''
    editComponentWeight.value = ''
    editComponentMax.value = ''
  }
  
  const saveComponentEdit = async (id) => {
    // Prevent editing if total would exceed 70%
    const newTotal = totalComponentWeight.value - components.value.find(c => c.id === id).weight + Number(editComponentWeight.value)
    if (newTotal > 70) {
      componentMsg.value = 'Total component weight cannot exceed 70%.'
      return
    }
    try {
      await api.put(`/components/${id}`, {
        component_name: editComponentName.value,
        weight: editComponentWeight.value,
        max_marks: editComponentMax.value
      })
      componentMsg.value = 'Component updated!'
      cancelEditComponent()
      await fetchComponents()
    } catch (e) {
      componentMsg.value = 'Failed to update component.'
    }
  }
  
  const enrollStudent = async () => {
    try {
      // Find student by matric number
      const res = await api.get(`/students?matric_number=${enrollMatric.value}`)
      const student = res.data.students[0]
      if (!student) {
        enrollMsg.value = 'Student not found.'
        return
      }
      await api.post('/enrollments', {
        student_id: student.id,
        course_id: selectedCourse.value
      })
      enrollMsg.value = 'Student enrolled!'
      enrollMatric.value = ''
      fetchStudents()
    } catch (e) {
      enrollMsg.value = e.response?.data?.message || 'Failed to enroll student.'
    }
  }
  
  const fetchAllStudents = async () => {
    const res = await api.get('/students')
    allStudents.value = res.data.students
  }
  
  const addStudent = async () => {
    try {
      await api.post('/students', {
        matric_number: newStudentMatric.value,
        full_name: newStudentName.value,
        email: newStudentEmail.value,
        pin: newStudentPin.value
      })
      studentMsg.value = 'Student added!'
      newStudentMatric.value = ''
      newStudentName.value = ''
      newStudentEmail.value = ''
      newStudentPin.value = ''
      fetchAllStudents()
    } catch (e) {
      studentMsg.value = 'Failed to add student.'
    }
  }
  
  const startStudentEdit = (student) => {
    editStudentId.value = student.id
    editStudentMatric.value = student.matric_number
    editStudentName.value = student.full_name
    editStudentEmail.value = student.email
    editStudentPin.value = ''
  }
  
  const cancelStudentEdit = () => {
    editStudentId.value = null
    editStudentMatric.value = ''
    editStudentName.value = ''
    editStudentEmail.value = ''
    editStudentPin.value = ''
  }
  
  const saveStudentEdit = async (id) => {
    try {
      await api.put(`/students/${id}`, {
        matric_number: editStudentMatric.value,
        full_name: editStudentName.value,
        email: editStudentEmail.value,
        pin: editStudentPin.value || undefined
      })
      studentMsg.value = 'Student updated!'
      cancelStudentEdit()
      fetchAllStudents()
    } catch (e) {
      studentMsg.value = 'Failed to update student.'
    }
  }
  
  const deleteStudent = async (id) => {
    try {
      await api.delete(`/students/${id}`)
      studentMsg.value = 'Student deleted!'
      fetchAllStudents()
    } catch (e) {
      studentMsg.value = 'Failed to delete student.'
    }
  }
  
  const addCourse = async () => {
    try {
      await api.post('/courses', {
        course_code: newCourseCode.value,
        course_name: newCourseName.value,
        lecturer_id: lecturer.value.id
      })
      courseMsg.value = 'Course added!'
      newCourseCode.value = ''
      newCourseName.value = ''
      await fetchCourses()
    } catch (e) {
      courseMsg.value = e.response?.data?.message || 'Failed to add course.'
    }
  }
  
  const startEditCourse = (course) => {
    editCourseId.value = course.id
    editCourseCode.value = course.course_code
    editCourseName.value = course.course_name
  }
  
  const cancelEditCourse = () => {
    editCourseId.value = null
    editCourseCode.value = ''
    editCourseName.value = ''
  }
  
  const saveCourseEdit = async (id) => {
    try {
      await api.put(`/courses/${id}`, {
        course_code: editCourseCode.value,
        course_name: editCourseName.value,
        lecturer_id: lecturer.value.id
      })
      courseMsg.value = 'Course updated!'
      cancelEditCourse()
      await fetchCourses()
    } catch (e) {
      courseMsg.value = e.response?.data?.message || 'Failed to update course.'
    }
  }
  
  const deleteCourse = async (id) => {
    if (!confirm('Are you sure you want to delete this course?')) return
    try {
      await api.delete(`/courses/${id}`)
      courseMsg.value = 'Course deleted!'
      if (selectedCourse.value === id) selectedCourse.value = null
      await fetchCourses()
    } catch (e) {
      courseMsg.value = e.response?.data?.message || 'Failed to delete course.'
    }
  }
  
  onMounted(async () => {
    await fetchCourses()
    if (selectedCourse.value) {
      await fetchComponents()
      await fetchStudents()
      await fetchMarks()
      await fetchAnalytics()
    }
    fetchAllStudents()
  })
  
  watch([selectedCourse, components], async () => {
    await fetchStudents()
    await fetchMarks()
    await fetchAnalytics()
  })
  </script>