<template>
  <div>
    <h2>Admin Dashboard</h2>
    <div>
      <button @click="tab = 'courses'">Course Management</button>
      <button @click="tab = 'users'">User Management</button>
      <button @click="tab = 'logs'">System Activity Log</button>
      <button @click="tab = 'reset'">Reset Passwords</button>
    </div>
    <div v-if="tab === 'courses'">
      <h3>Course Management</h3>
      <button @click="showAdd = true">Add New Course</button>
      <table border="1" style="margin-top: 1rem;">
        <thead>
          <tr>
            <th>Course Code</th>
            <th>Course Name</th>
            <th>Lecturer ID</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="course in courses" :key="course.id">
            <template v-if="editId === course.id">
              <td><input v-model="editCode" /></td>
              <td><input v-model="editName" /></td>
              <td><input v-model="editLecturerId" /></td>
              <td>
                <button @click="saveEdit(course.id)">Save</button>
                <button @click="cancelEdit">Cancel</button>
              </td>
            </template>
            <template v-else>
              <td>{{ course.course_code }}</td>
              <td>{{ course.course_name }}</td>
              <td>{{ course.lecturer_id }}</td>
              <td>
                <button @click="startEdit(course)">Edit</button>
                <button @click="deleteCourse(course.id)">Delete</button>
              </td>
            </template>
          </tr>
        </tbody>
      </table>
      <div v-if="showAdd" style="margin-top: 1rem;">
        <h3>Add New Course</h3>
        <input v-model="newCode" placeholder="Course Code" />
        <input v-model="newName" placeholder="Course Name" />
        <input v-model="newLecturerId" placeholder="Lecturer ID" />
        <button @click="addCourse">Add</button>
        <button @click="showAdd = false">Cancel</button>
      </div>
      <div v-if="msg" style="margin-top: 1rem; color: green;">{{ msg }}</div>
    </div>
    <div v-if="tab === 'users'">
      <h3>User Management</h3>
      <table border="1">
        <thead>
          <tr>
            <th>User Type</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Username/Matric/Staff ID</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id + user.type">
            <td>{{ user.type }}</td>
            <td>{{ user.full_name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.username || user.matric_number || user.staff_id }}</td>
            <td>
              <button @click="startUserEdit(user)">Edit</button>
              <button @click="deleteUser(user)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="userMsg" style="color: green;">{{ userMsg }}</div>
    </div>
    <div v-if="tab === 'logs'">
      <h3>System Activity Log</h3>
      <ul>
        <li v-for="log in logs" :key="log.id">{{ log.timestamp }} - {{ log.action }}</li>
      </ul>
    </div>
    <div v-if="tab === 'reset'">
      <h3>Reset Passwords</h3>
      <form @submit.prevent="resetPassword">
        <select v-model="resetType">
          <option value="student">Student</option>
          <option value="lecturer">Lecturer</option>
          <option value="admin">Admin</option>
        </select>
        <input v-model="resetId" placeholder="User ID" />
        <input v-model="resetNew" placeholder="New Password" type="password" />
        <button type="submit">Reset</button>
      </form>
      <div v-if="resetMsg" style="color: green;">{{ resetMsg }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const tab = ref('courses')
// Course management
const courses = ref([])
const showAdd = ref(false)
const newCode = ref('')
const newName = ref('')
const newLecturerId = ref('')
const editId = ref(null)
const editCode = ref('')
const editName = ref('')
const editLecturerId = ref('')
const msg = ref('')
// User management
const users = ref([])
const userMsg = ref('')
// Logs
const logs = ref([])
// Password reset
const resetType = ref('student')
const resetId = ref('')
const resetNew = ref('')
const resetMsg = ref('')

const fetchCourses = async () => {
  const res = await api.get('/courses')
  courses.value = res.data.courses
}
const fetchUsers = async () => {
  const res = await api.get('/admin/users')
  users.value = []
  for (const type of ['students', 'lecturers', 'admins']) {
    for (const u of res.data[type] || []) {
      users.value.push({ ...u, type: type.slice(0, -1) })
    }
  }
}
const fetchLogs = async () => {
  const res = await api.get('/admin/logs')
  logs.value = res.data.logs
}
const addCourse = async () => {
  try {
    await api.post('/courses', {
      course_code: newCode.value,
      course_name: newName.value,
      lecturer_id: newLecturerId.value
    })
    msg.value = 'Course added!'
    showAdd.value = false
    newCode.value = ''
    newName.value = ''
    newLecturerId.value = ''
    fetchCourses()
  } catch (e) {
    msg.value = 'Failed to add course.'
  }

}
const startEdit = (course) => {
  editId.value = course.id
  editCode.value = course.course_code
  editName.value = course.course_name
  editLecturerId.value = course.lecturer_id
}
const cancelEdit = () => {
  editId.value = null
  editCode.value = ''
  editName.value = ''
  editLecturerId.value = ''
}
const saveEdit = async (id) => {
  try {
    await api.put(`/courses/${id}`, {
      course_code: editCode.value,
      course_name: editName.value,
      lecturer_id: editLecturerId.value
    })
    msg.value = 'Course updated!'
    cancelEdit()
    fetchCourses()
  } catch (e) {
    msg.value = 'Failed to update course.'
  }
}
const deleteCourse = async (id) => {
  try {
    await api.delete(`/courses/${id}`)
    msg.value = 'Course deleted!'
    fetchCourses()
  } catch (e) {
    msg.value = 'Failed to delete course.'
  }

}
const startUserEdit = () => {
  // For demo: just show a message
  userMsg.value = 'Edit user feature coming soon.'
}
const deleteUser = async (user) => {
  try {
    await api.delete(`/admin/users/${user.type}/${user.id}`)
    userMsg.value = 'User deleted!'
    fetchUsers()
  } catch (e) {
    userMsg.value = 'Failed to delete user.'
  }
}
const resetPassword = async () => {
  try {
    await api.post(`/admin/users/${resetType.value}/${resetId.value}/reset-password`, {
      new_password: resetNew.value
    })
    resetMsg.value = 'Password reset!'
    resetId.value = ''
    resetNew.value = ''
  } catch (e) {
    resetMsg.value = 'Failed to reset password.'
  }

}
onMounted(() => {
  fetchCourses()
  fetchUsers()
  fetchLogs()
})
</script> 