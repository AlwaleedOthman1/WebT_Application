<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    $app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
    $app->get('/api/hello/{name}', function (Request $request, Response $response, array $args) {
        $name = $args['name'];
        $response->getBody()->write("Hello, $name");
        return $response;
    });

    $app->post('/api/lecturer/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $staff_id = $data['staff_id'] ?? '';
        $password = $data['password'] ?? '';

        if (!$staff_id || !$password) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Staff ID and password are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        $stmt = $db->prepare('SELECT * FROM lecturers WHERE staff_id = ?');
        $stmt->execute([$staff_id]);
        $lecturer = $stmt->fetch();

        if ($lecturer && password_verify($password, $lecturer['password_hash'])) {
            unset($lecturer['password_hash']);
            $response->getBody()->write(json_encode([
                'success' => true,
                'lecturer' => $lecturer
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid staff ID or password.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    });

    $app->post('/api/lecturer/register', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $staff_id = trim($data['staff_id'] ?? '');
        $password = $data['password'] ?? '';
        $full_name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone_number = trim($data['phone_number'] ?? '');

        if (!$staff_id || !$password || !$full_name || !$email) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'All required fields must be provided.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check for duplicate staff_id or email
        $stmt = $db->prepare('SELECT id FROM lecturers WHERE staff_id = ? OR email = ?');
        $stmt->execute([$staff_id, $email]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Staff ID or email already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO lecturers (staff_id, password_hash, full_name, email, phone_number) VALUES (?, ?, ?, ?, ?)');
        $success = $stmt->execute([$staff_id, $password_hash, $full_name, $email, $phone_number]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Lecturer registered successfully.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Registration failed.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Student Registration
    $app->post('/api/student/register', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $matric_number = trim($data['matric_number'] ?? '');
        $pin = $data['pin'] ?? '';
        $full_name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $advisor_id = $data['advisor_id'] ?? null;
        $phone_number = trim($data['phone_number'] ?? '');

        if (!$matric_number || !$pin || !$full_name || !$email) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'All required fields must be provided.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check for duplicate matric_number or email
        $stmt = $db->prepare('SELECT id FROM students WHERE matric_number = ? OR email = ?');
        $stmt->execute([$matric_number, $email]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Matric number or email already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO students (matric_number, pin_hash, full_name, email, advisor_id) VALUES (?, ?, ?, ?, ?)');
        $success = $stmt->execute([$matric_number, $pin_hash, $full_name, $email, $advisor_id]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student registered successfully.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Registration failed.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Student Login
    $app->post('/api/student/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $matric_number = $data['matric_number'] ?? '';
        $pin = $data['pin'] ?? '';

        if (!$matric_number || !$pin) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Matric number and PIN are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        $stmt = $db->prepare('SELECT * FROM students WHERE matric_number = ?');
        $stmt->execute([$matric_number]);
        $student = $stmt->fetch();

        if ($student && password_verify($pin, $student['pin_hash'])) {
            unset($student['pin_hash']);
            $response->getBody()->write(json_encode([
                'success' => true,
                'student' => $student
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid matric number or PIN.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    });

    // Add a new course
    $app->post('/api/courses', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $course_code = trim($data['course_code'] ?? '');
        $course_name = trim($data['course_name'] ?? '');
        $lecturer_id = $data['lecturer_id'] ?? null;

        if (!$course_code || !$course_name) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Course code and name are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check for duplicate course_code
        $stmt = $db->prepare('SELECT id FROM courses WHERE course_code = ?');
        $stmt->execute([$course_code]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Course code already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $db->prepare('INSERT INTO courses (course_code, course_name, lecturer_id) VALUES (?, ?, ?)');
        $success = $stmt->execute([$course_code, $course_name, $lecturer_id]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Course added successfully.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to add course.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // List all courses
    $app->get('/api/courses', function (Request $request, Response $response) {
        $db = $this->get('db');
        $stmt = $db->query('SELECT * FROM courses');
        $courses = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'courses' => $courses
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Edit a course
    $app->put('/api/courses/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $course_code = trim($data['course_code'] ?? '');
        $course_name = trim($data['course_name'] ?? '');
        $lecturer_id = $data['lecturer_id'] ?? null;

        if (!$course_code || !$course_name) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Course code and name are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check for duplicate course_code (excluding current course)
        $stmt = $db->prepare('SELECT id FROM courses WHERE course_code = ? AND id != ?');
        $stmt->execute([$course_code, $id]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Course code already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $db->prepare('UPDATE courses SET course_code = ?, course_name = ?, lecturer_id = ? WHERE id = ?');
        $success = $stmt->execute([$course_code, $course_name, $lecturer_id, $id]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Course updated successfully.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update course.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Delete a course
    $app->delete('/api/courses/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $db = $this->get('db');
        $stmt = $db->prepare('DELETE FROM courses WHERE id = ?');
        $success = $stmt->execute([$id]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Course deleted successfully.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete course.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Assign a lecturer to a course
    $app->post('/api/courses/{id}/assign-lecturer', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $lecturer_id = $data['lecturer_id'] ?? null;
        if (!$lecturer_id) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Lecturer ID is required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        $stmt = $db->prepare('UPDATE courses SET lecturer_id = ? WHERE id = ?');
        $success = $stmt->execute([$lecturer_id, $id]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Lecturer assigned to course.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to assign lecturer.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Enroll a student in a course
    $app->post('/api/enrollments', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $student_id = $data['student_id'] ?? null;
        $course_id = $data['course_id'] ?? null;

        if (!$student_id || !$course_id) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Student ID and Course ID are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check if already enrolled
        $stmt = $db->prepare('SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?');
        $stmt->execute([$student_id, $course_id]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Student is already enrolled in this course.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $db->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)');
        $success = $stmt->execute([$student_id, $course_id]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student enrolled successfully.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Enrollment failed.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // List all courses a student is enrolled in
    $app->get('/api/students/{student_id}/courses', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT c.* FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = ?');
        $stmt->execute([$student_id]);
        $courses = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'courses' => $courses
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // List all students enrolled in a course
    $app->get('/api/courses/{course_id}/students', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT s.* FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ?');
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'students' => $students
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Add an assessment component to a course
    $app->post('/api/courses/{course_id}/components', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $data = $request->getParsedBody();
        $component_name = trim($data['component_name'] ?? '');
        $weight = $data['weight'] ?? null;
        $max_marks = $data['max_marks'] ?? null;

        if (!$component_name || $weight === null || $max_marks === null) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Component name, weight, and max marks are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        $stmt = $db->prepare('INSERT INTO assessment_components (course_id, component_name, weight, max_marks) VALUES (?, ?, ?, ?)');
        $success = $stmt->execute([$course_id, $component_name, $weight, $max_marks]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Assessment component added.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to add assessment component.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // List all assessment components for a course
    $app->get('/api/courses/{course_id}/components', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT * FROM assessment_components WHERE course_id = ?');
        $stmt->execute([$course_id]);
        $components = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'components' => $components
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Edit an assessment component
    $app->put('/api/components/{component_id}', function (Request $request, Response $response, array $args) {
        $component_id = $args['component_id'];
        $data = $request->getParsedBody();
        $component_name = trim($data['component_name'] ?? '');
        $weight = $data['weight'] ?? null;
        $max_marks = $data['max_marks'] ?? null;

        if (!$component_name || $weight === null || $max_marks === null) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Component name, weight, and max marks are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        $stmt = $db->prepare('UPDATE assessment_components SET component_name = ?, weight = ?, max_marks = ? WHERE id = ?');
        $success = $stmt->execute([$component_name, $weight, $max_marks, $component_id]);

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Assessment component updated.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update assessment component.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Delete an assessment component
    $app->delete('/api/components/{component_id}', function (Request $request, Response $response, array $args) {
        $component_id = $args['component_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('DELETE FROM assessment_components WHERE id = ?');
        $success = $stmt->execute([$component_id]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Assessment component deleted.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete assessment component.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Enter or update a student's mark for an assessment component
    $app->post('/api/marks', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $student_id = $data['student_id'] ?? null;
        $component_id = $data['component_id'] ?? null;
        $marks_obtained = $data['marks_obtained'] ?? null;
        $lecturer_id = $data['lecturer_id'] ?? null;

        if (!$student_id || !$component_id || $marks_obtained === null) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Student ID, component ID, and marks are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check if mark already exists
        $stmt = $db->prepare('SELECT id FROM marks WHERE student_id = ? AND component_id = ?');
        $stmt->execute([$student_id, $component_id]);
        $existing = $stmt->fetch();
        if ($existing) {
            // Update
            $stmt = $db->prepare('UPDATE marks SET marks_obtained = ?, recorded_by_lecturer_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $success = $stmt->execute([$marks_obtained, $lecturer_id, $existing['id']]);
        } else {
            // Insert
            $stmt = $db->prepare('INSERT INTO marks (student_id, component_id, marks_obtained, recorded_by_lecturer_id) VALUES (?, ?, ?, ?)');
            $success = $stmt->execute([$student_id, $component_id, $marks_obtained, $lecturer_id]);
        }

        // Notification
        if ($success) {
            $stmt = $db->prepare('SELECT component_name FROM assessment_components WHERE id = ?');
            $stmt->execute([$component_id]);
            $component = $stmt->fetch();
            $message = $component ? ("Your mark for " . $component['component_name'] . " has been updated to " . $marks_obtained . ".") : "Your mark has been updated.";
            createStudentNotification($db, $student_id, $message);
        }

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Mark saved.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to save mark.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Enter or update a student's final exam mark for a course
    $app->post('/api/final-exam-marks', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $student_id = $data['student_id'] ?? null;
        $course_id = $data['course_id'] ?? null;
        $marks_obtained = $data['marks_obtained'] ?? null;
        $lecturer_id = $data['lecturer_id'] ?? null;

        if (!$student_id || !$course_id || $marks_obtained === null) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Student ID, course ID, and marks are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = $this->get('db');
        // Check if final exam mark already exists
        $stmt = $db->prepare('SELECT id FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
        $stmt->execute([$student_id, $course_id]);
        $existing = $stmt->fetch();
        if ($existing) {
            // Update
            $stmt = $db->prepare('UPDATE final_exam_marks SET marks_obtained = ?, recorded_by_lecturer_id = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?');
            $success = $stmt->execute([$marks_obtained, $lecturer_id, $existing['id']]);
        } else {
            // Insert
            $stmt = $db->prepare('INSERT INTO final_exam_marks (student_id, course_id, marks_obtained, recorded_by_lecturer_id) VALUES (?, ?, ?, ?)');
            $success = $stmt->execute([$student_id, $course_id, $marks_obtained, $lecturer_id]);
        }

        // Notification
        if ($success) {
            $message = "Your final exam mark for this course has been updated to $marks_obtained.";
            createStudentNotification($db, $student_id, $message);
        }

        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Final exam mark saved.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to save final exam mark.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // List all marks for a student in a course (component-wise and final)
    $app->get('/api/students/{student_id}/courses/{course_id}/marks', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $course_id = $args['course_id'];
        $db = $this->get('db');
        // Get component marks
        $stmt = $db->prepare('SELECT ac.component_name, ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
        $stmt->execute([$student_id, $course_id]);
        $component_marks = $stmt->fetchAll();
        // Get final exam mark
        $stmt = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
        $stmt->execute([$student_id, $course_id]);
        $final_exam = $stmt->fetch();
        $response->getBody()->write(json_encode([
            'success' => true,
            'component_marks' => $component_marks,
            'final_exam_mark' => $final_exam['marks_obtained'] ?? null
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get student dashboard data for a course
    $app->get('/api/students/{student_id}/courses/{course_id}/dashboard', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $course_id = $args['course_id'];
        $db = $this->get('db');
        // Get component marks
        $stmt = $db->prepare('SELECT ac.component_name, ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
        $stmt->execute([$student_id, $course_id]);
        $component_marks = $stmt->fetchAll();
        // Get final exam mark
        $stmt = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
        $stmt->execute([$student_id, $course_id]);
        $final_exam = $stmt->fetch();
        // Calculate totals
        $continuous_total = 0;
        $continuous_max = 0;
        foreach ($component_marks as $cm) {
            if ($cm['marks_obtained'] !== null) {
                $continuous_total += ($cm['marks_obtained'] / $cm['max_marks']) * $cm['weight'];
            }
            $continuous_max += $cm['weight'];
        }
        $final_exam_mark = $final_exam['marks_obtained'] ?? null;
        $total = null;
        if ($continuous_max > 0 && $final_exam_mark !== null) {
            $total = $continuous_total + ($final_exam_mark * 0.3); // assuming final exam is out of 100 and is 30%
        }
        // Fetch component averages for the course
        $stmt_avg = $db->prepare('SELECT id, component_name FROM assessment_components WHERE course_id = ? ORDER BY id');
        $stmt_avg->execute([$course_id]);
        $components_avg = $stmt_avg->fetchAll();
        $avg_labels = [];
        $avg_averages = [];
        foreach ($components_avg as $comp) {
            $avg_labels[] = $comp['component_name'];
            $stmt2 = $db->prepare('SELECT AVG(marks_obtained) as avg_mark FROM marks WHERE component_id = ?');
            $stmt2->execute([$comp['id']]);
            $avg = $stmt2->fetch();
            $avg_averages[] = $avg['avg_mark'] !== null ? (float)$avg['avg_mark'] : 0;
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'component_marks' => $component_marks,
            'final_exam_mark' => $final_exam_mark,
            'continuous_total' => $continuous_total,
            'total' => $total,
            'class_avg_labels' => $avg_labels,
            'class_avg_averages' => $avg_averages
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Compare marks anonymously with coursemates
    $app->get('/api/courses/{course_id}/marks/compare', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $db = $this->get('db');
        // Get all students in the course
        $stmt = $db->prepare('SELECT s.id as student_id FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ?');
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        $results = [];
        foreach ($students as $s) {
            $student_id = $s['student_id'];
            // Get component marks
            $stmt2 = $db->prepare('SELECT ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
            $stmt2->execute([$student_id, $course_id]);
            $component_marks = $stmt2->fetchAll();
            $continuous_total = 0;
            $continuous_max = 0;
            foreach ($component_marks as $cm) {
                if ($cm['marks_obtained'] !== null) {
                    $continuous_total += ($cm['marks_obtained'] / $cm['max_marks']) * $cm['weight'];
                }
                $continuous_max += $cm['weight'];
            }
            // Get final exam mark
            $stmt3 = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
            $stmt3->execute([$student_id, $course_id]);
            $final_exam = $stmt3->fetch();
            $final_exam_mark = $final_exam['marks_obtained'] ?? null;
            $total = null;
            if ($continuous_max > 0 && $final_exam_mark !== null) {
                $total = $continuous_total + ($final_exam_mark * 0.3);
            }
            $results[] = [
                'anon_id' => substr(md5($student_id), 0, 8),
                'total' => $total
            ];
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'results' => $results
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get student's rank and percentile in a course
    $app->get('/api/students/{student_id}/courses/{course_id}/rank', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $course_id = $args['course_id'];
        $db = $this->get('db');
        // Get all students in the course
        $stmt = $db->prepare('SELECT s.id as student_id FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ?');
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        $totals = [];
        foreach ($students as $s) {
            $sid = $s['student_id'];
            // Get component marks
            $stmt2 = $db->prepare('SELECT ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
            $stmt2->execute([$sid, $course_id]);
            $component_marks = $stmt2->fetchAll();
            $continuous_total = 0;
            $continuous_max = 0;
            foreach ($component_marks as $cm) {
                if ($cm['marks_obtained'] !== null) {
                    $continuous_total += ($cm['marks_obtained'] / $cm['max_marks']) * $cm['weight'];
                }
                $continuous_max += $cm['weight'];
            }
            // Get final exam mark
            $stmt3 = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
            $stmt3->execute([$sid, $course_id]);
            $final_exam = $stmt3->fetch();
            $final_exam_mark = $final_exam['marks_obtained'] ?? null;
            $total = null;
            if ($continuous_max > 0 && $final_exam_mark !== null) {
                $total = $continuous_total + ($final_exam_mark * 0.3);
            }
            $totals[$sid] = $total;
        }
        // Sort totals descending
        arsort($totals);
        $rank = 1;
        $student_rank = null;
        $student_total = $totals[$student_id] ?? null;
        foreach ($totals as $sid => $total) {
            if ($sid == $student_id) {
                $student_rank = $rank;
                break;
            }
            $rank++;
        }
        $percentile = null;
        if ($student_total !== null) {
            $num_students = count($totals);
            $num_below = 0;
            foreach ($totals as $t) {
                if ($t !== null && $t < $student_total) {
                    $num_below++;
                }
            }
            $percentile = ($num_below / $num_students) * 100;
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'rank' => $student_rank,
            'percentile' => $percentile
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Submit remark request
    $app->post('/api/remark-requests', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $student_id = $data['student_id'] ?? null;
        $course_id = $data['course_id'] ?? null;
        $component_id = $data['component_id'] ?? null;
        $justification = trim($data['justification'] ?? '');
        if (!$student_id || !$course_id || !$justification) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Student ID, course ID, and justification are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        $stmt = $db->prepare('INSERT INTO remark_requests (student_id, course_id, component_id, justification) VALUES (?, ?, ?, ?)');
        $success = $stmt->execute([$student_id, $course_id, $component_id, $justification]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Remark request submitted.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to submit remark request.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // List all advisees for an advisor
    $app->get('/api/advisors/{advisor_id}/advisees', function (Request $request, Response $response, array $args) {
        $advisor_id = $args['advisor_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT * FROM students WHERE advisor_id = ?');
        $stmt->execute([$advisor_id]);
        $advisees = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'advisees' => $advisees
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get full mark breakdown for an advisee across all courses
    $app->get('/api/advisors/{advisor_id}/advisees/{student_id}/marks', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $db = $this->get('db');
        // Get all courses for the student
        $stmt = $db->prepare('SELECT c.id as course_id, c.course_code, c.course_name FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = ?');
        $stmt->execute([$student_id]);
        $courses = $stmt->fetchAll();
        $marks = [];
        foreach ($courses as $course) {
            $course_id = $course['course_id'];
            // Get component marks
            $stmt2 = $db->prepare('SELECT ac.component_name, ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
            $stmt2->execute([$student_id, $course_id]);
            $component_marks = $stmt2->fetchAll();
            // Get final exam mark
            $stmt3 = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
            $stmt3->execute([$student_id, $course_id]);
            $final_exam = $stmt3->fetch();
            $marks[] = [
                'course' => $course,
                'component_marks' => $component_marks,
                'final_exam_mark' => $final_exam['marks_obtained'] ?? null
            ];
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'marks' => $marks
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Highlight at-risk students (bottom 20% by total marks in any course)
    $app->get('/api/advisors/{advisor_id}/advisees/at-risk', function (Request $request, Response $response, array $args) {
        $advisor_id = $args['advisor_id'];
        $db = $this->get('db');
        // Get all advisees
        $stmt = $db->prepare('SELECT * FROM students WHERE advisor_id = ?');
        $stmt->execute([$advisor_id]);
        $advisees = $stmt->fetchAll();
        $at_risk = [];
        foreach ($advisees as $student) {
            $student_id = $student['id'];
            // Get all courses for the student
            $stmt2 = $db->prepare('SELECT c.id as course_id FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = ?');
            $stmt2->execute([$student_id]);
            $courses = $stmt2->fetchAll();
            foreach ($courses as $course) {
                $course_id = $course['course_id'];
                // Get component marks
                $stmt3 = $db->prepare('SELECT ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
                $stmt3->execute([$student_id, $course_id]);
                $component_marks = $stmt3->fetchAll();
                $continuous_total = 0;
                $continuous_max = 0;
                foreach ($component_marks as $cm) {
                    if ($cm['marks_obtained'] !== null) {
                        $continuous_total += ($cm['marks_obtained'] / $cm['max_marks']) * $cm['weight'];
                    }
                    $continuous_max += $cm['weight'];
                }
                // Get final exam mark
                $stmt4 = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
                $stmt4->execute([$student_id, $course_id]);
                $final_exam = $stmt4->fetch();
                $final_exam_mark = $final_exam['marks_obtained'] ?? null;
                $total = null;
                if ($continuous_max > 0 && $final_exam_mark !== null) {
                    $total = $continuous_total + ($final_exam_mark * 0.3);
                }
                if ($total !== null) {
                    $at_risk[] = [
                        'student' => $student,
                        'course_id' => $course_id,
                        'total' => $total
                    ];
                }
            }
        }
        // Sort by total ascending and take bottom 20%
        usort($at_risk, function($a, $b) { return $a['total'] <=> $b['total']; });
        $count = count($at_risk);
        $bottom_20 = array_slice($at_risk, 0, max(1, ceil($count * 0.2)));
        $response->getBody()->write(json_encode([
            'success' => true,
            'at_risk' => $bottom_20
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Add a private note/meeting record for an advisee
    $app->post('/api/advisors/{advisor_id}/advisees/{student_id}/notes', function (Request $request, Response $response, array $args) {
        $advisor_id = $args['advisor_id'];
        $student_id = $args['student_id'];
        $data = $request->getParsedBody();
        $note = trim($data['note'] ?? '');
        if (!$note) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Note is required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        $stmt = $db->prepare('INSERT INTO advisor_notes (advisor_id, student_id, note) VALUES (?, ?, ?)');
        $success = $stmt->execute([$advisor_id, $student_id, $note]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Note added.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to add note.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // List all notes for an advisee
    $app->get('/api/advisors/{advisor_id}/advisees/{student_id}/notes', function (Request $request, Response $response, array $args) {
        $advisor_id = $args['advisor_id'];
        $student_id = $args['student_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT * FROM advisor_notes WHERE advisor_id = ? AND student_id = ? ORDER BY created_at DESC');
        $stmt->execute([$advisor_id, $student_id]);
        $notes = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'notes' => $notes
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // List all users (lecturers, students, admins)
    $app->get('/api/admin/users', function (Request $request, Response $response) {
        $db = $this->get('db');
        $lecturers = $db->query('SELECT id, staff_id, full_name, email, phone_number, created_at FROM lecturers')->fetchAll();
        $students = $db->query('SELECT id, matric_number, full_name, email, advisor_id, created_at FROM students')->fetchAll();
        $admins = $db->query('SELECT id, username, full_name, email, created_at FROM admins')->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'lecturers' => $lecturers,
            'students' => $students,
            'admins' => $admins
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new admin user
    $app->post('/api/admin/users', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $email = trim($data['email'] ?? '');
        $full_name = trim($data['full_name'] ?? '');
        if (!$username || !$password || !$email) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Username, password, and email are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        // Check for duplicate username or email
        $stmt = $db->prepare('SELECT id FROM admins WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Username or email already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO admins (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)');
        $success = $stmt->execute([$username, $password_hash, $email, $full_name]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Admin user created.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create admin user.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Reset a user's password
    $app->post('/api/admin/users/{user_type}/{user_id}/reset-password', function (Request $request, Response $response, array $args) {
        $user_type = $args['user_type'];
        $user_id = $args['user_id'];
        $data = $request->getParsedBody();
        $new_password = $data['new_password'] ?? '';
        if (!$new_password) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'New password is required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $table = null;
        $field = null;
        switch ($user_type) {
            case 'lecturer':
                $table = 'lecturers';
                $field = 'password_hash';
                break;
            case 'student':
                $table = 'students';
                $field = 'pin_hash';
                break;
            case 'admin':
                $table = 'admins';
                $field = 'password_hash';
                break;
            default:
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid user type.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $stmt = $db->prepare("UPDATE $table SET $field = ? WHERE id = ?");
        $success = $stmt->execute([$password_hash, $user_id]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Password reset.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to reset password.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // View system logs (placeholder)
    $app->get('/api/admin/logs', function (Request $request, Response $response) {
        // In a real app, you would read from a log file or database
        $logs = [
            [ 'timestamp' => date('Y-m-d H:i:s'), 'event' => 'System started.' ],
            [ 'timestamp' => date('Y-m-d H:i:s'), 'event' => 'User management event.' ]
        ];
        $response->getBody()->write(json_encode([
            'success' => true,
            'logs' => $logs
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // List all notifications for a student
    $app->get('/api/students/{student_id}/notifications', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? AND user_type = "student" ORDER BY created_at DESC');
        $stmt->execute([$student_id]);
        $notifications = $stmt->fetchAll();
        $response->getBody()->write(json_encode([
            'success' => true,
            'notifications' => $notifications
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Mark a notification as read
    $app->post('/api/notifications/{notification_id}/read', function (Request $request, Response $response, array $args) {
        $notification_id = $args['notification_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        $success = $stmt->execute([$notification_id]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notification marked as read.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to mark notification as read.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Helper function to create a notification for a student
    function createStudentNotification($db, $student_id, $message) {
        $stmt = $db->prepare('INSERT INTO notifications (user_id, user_type, message) VALUES (?, "student", ?)');
        $stmt->execute([$student_id, $message]);
    }

    // Export all marks for a course as CSV
    $app->get('/api/courses/{course_id}/export-marks', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $db = $this->get('db');
        // Get all assessment components for the course
        $stmt = $db->prepare('SELECT id, component_name FROM assessment_components WHERE course_id = ? ORDER BY id');
        $stmt->execute([$course_id]);
        $components = $stmt->fetchAll();
        // Get all students enrolled in the course
        $stmt = $db->prepare('SELECT s.id, s.full_name, s.matric_number FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ?');
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        // Prepare CSV header
        $header = ['Matric Number', 'Full Name'];
        foreach ($components as $comp) {
            $header[] = $comp['component_name'];
        }
        $header[] = 'Final Exam';
        $header[] = 'Total';
        // Prepare CSV rows
        $rows = [];
        $stmt_final = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
        foreach ($students as $student) {
            $row = [$student['matric_number'], $student['full_name']];
            $total = 0;
            $continuous_max = 0;
            // Component marks
            foreach ($components as $comp) {
                $stmt2 = $db->prepare('SELECT marks_obtained, max_marks, weight FROM marks m JOIN assessment_components ac ON m.component_id = ac.id WHERE m.student_id = ? AND m.component_id = ?');
                $stmt2->execute([$student['id'], $comp['id']]);
                $mark = $stmt2->fetch();
                if ($mark) {
                    $row[] = $mark['marks_obtained'];
                    $total += ($mark['marks_obtained'] / $mark['max_marks']) * $mark['weight'];
                    $continuous_max += $mark['weight'];
                } else {
                    $row[] = '';
                    $continuous_max += $comp['weight'] ?? 0;
                }
            }
            // Final exam
            $stmt_final->execute([$student['id'], $course_id]);
            $final_exam = $stmt_final->fetch();
            $final_exam_mark = $final_exam['marks_obtained'] ?? '';
            $row[] = $final_exam_mark;
            // Total
            $grand_total = $total;
            if ($final_exam_mark !== '') {
                $grand_total += ($final_exam_mark * 0.3); // assuming final exam is 30%
            }
            $row[] = $grand_total;
            $rows[] = $row;
        }
        // Output CSV
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $header);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="course_' . $course_id . '_marks.csv"');
    });

    // Student progress data for Chart.js
    $app->get('/api/students/{student_id}/courses/{course_id}/progress-data', function (Request $request, Response $response, array $args) {
        $student_id = $args['student_id'];
        $course_id = $args['course_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT ac.component_name, ac.max_marks, ac.weight, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ? ORDER BY ac.id');
        $stmt->execute([$student_id, $course_id]);
        $components = $stmt->fetchAll();
        $labels = [];
        $data = [];
        $max_marks = [];
        $weights = [];
        foreach ($components as $c) {
            $labels[] = $c['component_name'];
            $data[] = $c['marks_obtained'] !== null ? (float)$c['marks_obtained'] : null;
            $max_marks[] = (float)$c['max_marks'];
            $weights[] = (float)$c['weight'];
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'labels' => $labels,
            'data' => $data,
            'max_marks' => $max_marks,
            'weights' => $weights
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Course mark distribution for Chart.js
    $app->get('/api/courses/{course_id}/distribution', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT s.id as student_id FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ?');
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        $totals = [];
        foreach ($students as $s) {
            $student_id = $s['student_id'];
            $stmt2 = $db->prepare('SELECT ac.weight, ac.max_marks, m.marks_obtained FROM assessment_components ac LEFT JOIN marks m ON ac.id = m.component_id AND m.student_id = ? WHERE ac.course_id = ?');
            $stmt2->execute([$student_id, $course_id]);
            $component_marks = $stmt2->fetchAll();
            $continuous_total = 0;
            $continuous_max = 0;
            foreach ($component_marks as $cm) {
                if ($cm['marks_obtained'] !== null) {
                    $continuous_total += ($cm['marks_obtained'] / $cm['max_marks']) * $cm['weight'];
                }
                $continuous_max += $cm['weight'];
            }
            $stmt3 = $db->prepare('SELECT marks_obtained FROM final_exam_marks WHERE student_id = ? AND course_id = ?');
            $stmt3->execute([$student_id, $course_id]);
            $final_exam = $stmt3->fetch();
            $final_exam_mark = $final_exam['marks_obtained'] ?? null;
            $total = null;
            if ($continuous_max > 0 && $final_exam_mark !== null) {
                $total = $continuous_total + ($final_exam_mark * 0.3);
            }
            $totals[] = $total;
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'totals' => $totals
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Component averages for Chart.js
    $app->get('/api/courses/{course_id}/component-averages', function (Request $request, Response $response, array $args) {
        $course_id = $args['course_id'];
        $db = $this->get('db');
        $stmt = $db->prepare('SELECT id, component_name, max_marks FROM assessment_components WHERE course_id = ? ORDER BY id');
        $stmt->execute([$course_id]);
        $components = $stmt->fetchAll();
        $labels = [];
        $averages = [];
        foreach ($components as $comp) {
            $labels[] = $comp['component_name'];
            $stmt2 = $db->prepare('SELECT AVG(marks_obtained) as avg_mark FROM marks WHERE component_id = ?');
            $stmt2->execute([$comp['id']]);
            $avg = $stmt2->fetch();
            $averages[] = $avg['avg_mark'] !== null ? (float)$avg['avg_mark'] : 0;
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'labels' => $labels,
            'averages' => $averages
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // List all students (with optional matric_number filter)
    $app->get('/api/students', function ($request, $response) {
        $db = $this->get('db');
        $params = $request->getQueryParams();
        if (!empty($params['matric_number'])) {
            $stmt = $db->prepare('SELECT id, matric_number, full_name, email FROM students WHERE matric_number = ?');
            $stmt->execute([$params['matric_number']]);
            $students = $stmt->fetchAll();
        } else {
            $stmt = $db->query('SELECT id, matric_number, full_name, email FROM students');
            $students = $stmt->fetchAll();
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'students' => $students
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Edit a student
    $app->put('/api/students/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $matric_number = trim($data['matric_number'] ?? '');
        $full_name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $pin = $data['pin'] ?? null;
        if (!$matric_number || !$full_name || !$email) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Matric number, full name, and email are required.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        // Check for duplicate matric_number or email (excluding current student)
        $stmt = $db->prepare('SELECT id FROM students WHERE (matric_number = ? OR email = ?) AND id != ?');
        $stmt->execute([$matric_number, $email, $id]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Matric number or email already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }
        if ($pin) {
            $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE students SET matric_number = ?, full_name = ?, email = ?, pin_hash = ? WHERE id = ?');
            $success = $stmt->execute([$matric_number, $full_name, $email, $pin_hash, $id]);
        } else {
            $stmt = $db->prepare('UPDATE students SET matric_number = ?, full_name = ?, email = ? WHERE id = ?');
            $success = $stmt->execute([$matric_number, $full_name, $email, $id]);
        }
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student updated.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update student.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Delete a student
    $app->delete('/api/students/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $db = $this->get('db');
        $stmt = $db->prepare('DELETE FROM students WHERE id = ?');
        $success = $stmt->execute([$id]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student deleted.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete student.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Add a new student
    $app->post('/api/students', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $matric_number = trim($data['matric_number'] ?? '');
        $pin = $data['pin'] ?? '';
        $full_name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        if (!$matric_number || !$pin || !$full_name || !$email) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'All required fields must be provided.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $db = $this->get('db');
        // Check for duplicate matric_number or email
        $stmt = $db->prepare('SELECT id FROM students WHERE matric_number = ? OR email = ?');
        $stmt->execute([$matric_number, $email]);
        if ($stmt->fetch()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Matric number or email already exists.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }
        $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO students (matric_number, pin_hash, full_name, email) VALUES (?, ?, ?, ?)');
        $success = $stmt->execute([$matric_number, $pin_hash, $full_name, $email]);
        if ($success) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student added.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to add student.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });
}; 