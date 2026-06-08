<?php
require_once 'db.php';
require_once 'session_auth.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Track active side navigation state
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'view';

// Read form persistent tracking arrays (Used by both Teachers and Students)
$sel_class = isset($_POST['filter_class']) ? htmlspecialchars($_POST['filter_class']) : '';
$sel_subject = isset($_POST['filter_subject']) ? htmlspecialchars($_POST['filter_subject']) : '';
$sel_term = isset($_POST['filter_term']) ? htmlspecialchars($_POST['filter_term']) : '';

// Dedicated tracker for the Report tab hierarchical filter (Teachers only)
if ($active_tab === 'report' && isset($_POST['report_class'])) {
    $report_class = htmlspecialchars($_POST['report_class']);
} else {
    $report_class = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Tracking Portal Console</title>
    <link rel="stylesheet" href="Styles/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; }
        .sidebar { background-color: #ffffff; min-height: calc(100vh - 56px); box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .nav-link { color: #495057; font-weight: 500; border-radius: 8px; margin-bottom: 5px; cursor: pointer; }
        .nav-link.active { background-color: #e3f2fd !important; color: #0d6efd !important; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04); }
        
        .toast-popup {
            position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 8px;
            font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.1); z-index: 9999;
            opacity: 0; transform: translateY(-20px); transition: opacity 0.4s ease, transform 0.4s ease; pointer-events: none;
        }
        .toast-popup.show { opacity: 1; transform: translateY(0); }
        .toast-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .toast-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>

    <div id="statusToast" class="toast-popup"></div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm d-print-none">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#">Students' Grade Management System</a>
            <div class="ms-auto d-flex align-items-center text-white">
                <span class="me-3 small">Active User: <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo ucfirst($role); ?>)</span>
                <a href="logout.php" class="btn btn-sm btn-danger fw-bold text-white px-3">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3 d-print-none">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <?php if ($role === 'teacher'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($active_tab === 'view') ? 'active' : ''; ?>" href="dashboard.php?tab=view">Dashboard View</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($active_tab === 'enroll') ? 'active' : ''; ?>" href="dashboard.php?tab=enroll">Add Students</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($active_tab === 'marking') ? 'active' : ''; ?>" href="dashboard.php?tab=marking">Marking Student</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($active_tab === 'report') ? 'active' : ''; ?>" href="dashboard.php?tab=report">Generate Report</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link active" href="dashboard.php">My Transcripts</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                
                <?php if ($role === 'teacher'): ?>
                    
                    <?php if ($active_tab === 'view'): ?>
                        <div class="mb-4">
                            <h2 class="h3 fw-bold text-dark">Roster Directory View</h2>
                            <p class="text-muted small">Select all filtration layers to populate current class tracking logs.</p>
                        </div>

                        <div class="card card-custom p-4 bg-white mb-4">
                            <form method="POST" action="dashboard.php?tab=view" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Target Class Group</label>
                                    <select name="filter_class" class="form-select" required>
                                        <option value="">-- Select Class --</option>
                                        <?php
                                        $cl_res = $conn->query("SELECT DISTINCT email_or_class FROM users WHERE role='student' AND email_or_class != ''");
                                        while($cl = $cl_res->fetch_assoc()) {
                                            $selected = ($sel_class === $cl['email_or_class']) ? 'selected' : '';
                                            echo "<option value='{$cl['email_or_class']}' {$selected}>{$cl['email_or_class']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Subject Course Assignment</label>
                                    <select name="filter_subject" class="form-select" required>
                                        <option value="">-- Select Subject --</option>
                                        <option value="Mathematics" <?php if($sel_subject==='Mathematics') echo 'selected'; ?>>Mathematics</option>
                                        <option value="Optional Mathematics" <?php if($sel_subject==='Optional Mathematics') echo 'selected'; ?>>Optional Mathematics</option>
                                        <option value="Science" <?php if($sel_subject==='Science') echo 'selected'; ?>>Science</option>
                                        <option value="Environmental Science" <?php if($sel_subject==='Environmental Science') echo 'selected'; ?>>Environmental Science</option>
                                        <option value="English" <?php if($sel_subject==='English') echo 'selected'; ?>>English</option>
                                        <option value="Computer Science" <?php if($sel_subject==='Computer Science') echo 'selected'; ?>>Computer Science</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Target Term Period</label>
                                    <select name="filter_term" class="form-select" required>
                                        <option value="">-- Select Term --</option>
                                        <option value="1st Term" <?php if($sel_term==='1st Term') echo 'selected'; ?>>1st Term</option>
                                        <option value="2nd Term" <?php if($sel_term==='2nd Term') echo 'selected'; ?>>2nd Term</option>
                                        <option value="3rd Term" <?php if($sel_term==='3rd Term') echo 'selected'; ?>>3rd Term</option>
                                        <option value="Annual" <?php if($sel_term==='Annual') echo 'selected'; ?>>Annual</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">View Records</button>
                                </div>
                            </form>
                        </div>

                        <div class="card card-custom p-4 bg-white">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light text-secondary small text-uppercase">
                                        <tr>
                                            <th>Class Group</th>
                                            <th>Symbol No</th>
                                            <th>Student Name</th>
                                            <th>Subject Course</th>
                                            <th>Term</th>
                                            <th>Score (%)</th>
                                            <th>GPA</th>
                                            <th>Grade</th>
                                            <th class="text-end">Actions Control</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if (!empty($sel_class) && !empty($sel_subject) && !empty($sel_term)):
                                            $g_sql = "SELECT g.id, u.username, u.symbol_no, u.email_or_class, g.subject, g.term, g.percentage, g.gpa, g.letter_grade 
                                                      FROM grades g JOIN users u ON g.student_id = u.id 
                                                      WHERE u.email_or_class='$sel_class' AND g.subject='$sel_subject' AND g.term='$sel_term' AND g.teacher_id=$user_id";
                                            $g_res = $conn->query($g_sql);
                                            if ($g_res && $g_res->num_rows > 0):
                                                while($row = $g_res->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo $row['email_or_class']; ?></span></td>
                                            <td class="font-monospace small"><?php echo $row['symbol_no']; ?></td>
                                            <td class="fw-semibold"><?php echo $row['username']; ?></td>
                                            <td><strong><?php echo $row['subject']; ?></strong></td>
                                            <td><span class="badge bg-primary"><?php echo $row['term']; ?></span></td>
                                            <td><?php echo $row['percentage']; ?>%</td>
                                            <td><?php echo $row['gpa']; ?></td>
                                            <td><span class="badge bg-dark"><?php echo $row['letter_grade']; ?></span></td>
                                            <td class="text-end">
                                                <form action="grade_actions.php" method="POST" class="d-inline-block">
                                                    <input type="hidden" name="update_grade" value="1">
                                                    <input type="hidden" name="grade_id" value="<?php echo $row['id']; ?>">
                                                    <input type="number" step="0.01" min="0" max="100" name="percentage" class="form-control form-control-sm d-inline-block mx-1" style="width: 75px;" value="<?php echo $row['percentage']; ?>" required>
                                                    <button type="submit" class="btn btn-sm btn-secondary text-white">Update</button>
                                                </form>
                                                <a href="grade_actions.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger text-white" onclick="return confirm('Remove permanently?')">Remove</a>
                                            </td>
                                        </tr>
                                        <?php 
                                                endwhile;
                                            else:
                                                echo "<tr><td colspan='9' class='text-center text-muted py-3'>No grade tracking profiles mapped onto those filtered coordinates.</td></tr>";
                                            endif;
                                        else:
                                            echo "<tr><td colspan='9' class='text-center text-secondary py-3 fw-bold'>Please define Class, Subject, and Term variables above to fetch roster files.</td></tr>";
                                        endif;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($active_tab === 'enroll'): ?>
                        <div class="mb-4">
                            <h2 class="h3 fw-bold text-dark">Subject Elective Enrollment Engine</h2>
                            <p class="text-muted small">Select a Class and Subject to load candidate student rows available for enrollment tracking.</p>
                        </div>

                        <div class="card card-custom p-4 bg-white mb-4">
                            <form method="POST" action="dashboard.php?tab=enroll" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Target Class Group</label>
                                    <select name="filter_class" class="form-select" required>
                                        <option value="">-- Choose Class Group --</option>
                                        <?php
                                        $cl_res = $conn->query("SELECT DISTINCT email_or_class FROM users WHERE role='student' AND email_or_class != ''");
                                        while($cl = $cl_res->fetch_assoc()) {
                                            $selected = ($sel_class === $cl['email_or_class']) ? 'selected' : '';
                                            echo "<option value='{$cl['email_or_class']}' {$selected}>{$cl['email_or_class']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-secondary">Target Subject Track</label>
                                    <select name="filter_subject" class="form-select" required>
                                        <option value="">-- Select Subject Course Assignment --</option>
                                        <option value="Mathematics" <?php if($sel_subject==='Mathematics') echo 'selected'; ?>>Mathematics</option>
                                        <option value="Optional Mathematics" <?php if($sel_subject==='Optional Mathematics') echo 'selected'; ?>>Optional Mathematics</option>
                                        <option value="Science" <?php if($sel_subject==='Science') echo 'selected'; ?>>Science</option>
                                        <option value="Environmental Science" <?php if($sel_subject==='Environmental Science') echo 'selected'; ?>>Environmental Science</option>
                                        <option value="English" <?php if($sel_subject==='English') echo 'selected'; ?>>English</option>
                                        <option value="Computer Science" <?php if($sel_subject==='Computer Science') echo 'selected'; ?>>Computer Science</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Fetch Student Roster</button>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($sel_class) && !empty($sel_subject)): ?>
                        <form method="POST" action="grade_actions.php">
                            <input type="hidden" name="enroll_students" value="1">
                            <input type="hidden" name="subject" value="<?php echo $sel_subject; ?>">
                            
                            <div class="card card-custom p-4 bg-white">
                                <h5 class="fw-bold mb-3">Available Unenrolled Members in <?php echo $sel_class; ?></h5>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40px">Select</th>
                                                <th>Symbol Number</th>
                                                <th>Student Full Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $s_sql = "SELECT id, username, symbol_no FROM users 
                                                      WHERE role='student' AND email_or_class='$sel_class' 
                                                      AND id NOT IN (SELECT student_id FROM subject_enrollments WHERE subject='$sel_subject')";
                                            $s_res = $conn->query($s_sql);
                                            if ($s_res && $s_res->num_rows > 0):
                                                while($st = $s_res->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><input type="checkbox" name="students[]" value="<?php echo $st['id']; ?>" class="form-check-input"></td>
                                                <td class="font-monospace small"><?php echo $st['symbol_no']; ?></td>
                                                <td class="fw-semibold"><?php echo $st['username']; ?></td>
                                            </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                                echo "<tr><td colspan='3' class='text-center text-muted py-3'>All students in this class bracket are already mapped to this subject.</td></tr>";
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-success fw-bold px-4">Save Enrollment Mapping</button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($active_tab === 'marking'): ?>
                        <div class="mb-4">
                            <h2 class="h3 fw-bold text-dark">Terminal Marks Entry Console</h2>
                            <p class="text-muted small">Select the target Class, Subject, and Term. Only students enrolled in that specific subject will appear for evaluation.</p>
                        </div>

                        <div class="card card-custom p-4 bg-white mb-4">
                            <form method="POST" action="dashboard.php?tab=marking" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Target Class Group</label>
                                    <select name="filter_class" class="form-select" required>
                                        <option value="">-- Select Class --</option>
                                        <?php
                                        $cl_res = $conn->query("SELECT DISTINCT email_or_class FROM users WHERE role='student' AND email_or_class != ''");
                                        while($cl = $cl_res->fetch_assoc()) {
                                            $selected = ($sel_class === $cl['email_or_class']) ? 'selected' : '';
                                            echo "<option value='{$cl['email_or_class']}' {$selected}>{$cl['email_or_class']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Subject Assignment</label>
                                    <select name="filter_subject" class="form-select" required>
                                        <option value="">-- Select Subject --</option>
                                        <option value="Mathematics" <?php if($sel_subject==='Mathematics') echo 'selected'; ?>>Mathematics</option>
                                        <option value="Optional Mathematics" <?php if($sel_subject==='Optional Mathematics') echo 'selected'; ?>>Optional Mathematics</option>
                                        <option value="Science" <?php if($sel_subject==='Science') echo 'selected'; ?>>Science</option>
                                        <option value="Environmental Science" <?php if($sel_subject==='Environmental Science') echo 'selected'; ?>>Environmental Science</option>
                                        <option value="English" <?php if($sel_subject==='English') echo 'selected'; ?>>English</option>
                                        <option value="Computer Science" <?php if($sel_subject==='Computer Science') echo 'selected'; ?>>Computer Science</option>
                                        <option value="Accountancy" <?php if($sel_subject==='Accountancy') echo 'selected'; ?>>Accountancy</option>
                                        <option value="Nepali" <?php if($sel_subject==='Nepali') echo 'selected'; ?>>Nepali</option>
                                        <option value="Social Studies" <?php if($sel_subject==='Social Studies') echo 'selected'; ?>>Social Studies</option>
                                        <option value="C Programming" <?php if($sel_subject==='C Programming') echo 'selected'; ?>>C Programming</option>
                                        <option value="C++" <?php if($sel_subject==='C++') echo 'selected'; ?>>C++</option>
                                        <option value="Discrete Mathematics" <?php if($sel_subject==='Discrete Mathematics') echo 'selected'; ?>>Discrete Mathematics</option>
                                        <option value="Data Structure and Algorithm" <?php if($sel_subject==='Data Structure and Algorithm') echo 'selected'; ?>>Data Structure and Algorithm</option>
                                        <option value="Java" <?php if($sel_subject==='Java') echo 'selected'; ?>>Java</option>
                                        <option value="Web Technology" <?php if($sel_subject==='Web Technology') echo 'selected'; ?>>Web Technology</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Term</label>
                                    <select name="filter_term" class="form-select" required>
                                        <option value="">-- Choose Term --</option>
                                        <option value="1st Term" <?php if($sel_term==='1st Term') echo 'selected'; ?>>1st Term</option>
                                        <option value="2nd Term" <?php if($sel_term==='2nd Term') echo 'selected'; ?>>2nd Term</option>
                                        <option value="3rd Term" <?php if($sel_term==='3rd Term') echo 'selected'; ?>>3rd Term</option>
                                        <option value="Annual" <?php if($sel_term==='Annual') echo 'selected'; ?>>Annual</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Load Enrolled Students</button>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($sel_class) && !empty($sel_subject) && !empty($sel_term)): ?>
                        <div class="card card-custom p-4 bg-white">
                            <h5 class="fw-bold mb-3"><?php echo "$sel_subject ($sel_term)"; ?></h5>
                            <form method="POST" action="grade_actions.php" class="row g-3">
                                <input type="hidden" name="add_grade" value="1">
                                <input type="hidden" name="subject" value="<?php echo $sel_subject; ?>">
                                <input type="hidden" name="term" value="<?php echo $sel_term; ?>">

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Student</label>
                                    <select name="student_id" class="form-select" required>
                                        <option value="">-- Choose Certified Enrolled Student --</option>
                                        <?php 
                                        $m_sql = "SELECT u.id, u.username, u.symbol_no FROM users u 
                                                  JOIN subject_enrollments e ON u.id = e.student_id 
                                                  WHERE u.email_or_class='$sel_class' AND e.subject='$sel_subject'";
                                        $m_res = $conn->query($m_sql);
                                        while($st = $m_res->fetch_assoc()) {
                                            echo "<option value='{$st['id']}'>{$st['username']} ({$st['symbol_no']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Percentage (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" name="percentage" class="form-control" placeholder="0-100" required>
                                </div>

                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success w-100 fw-bold">Commit Grade</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($active_tab === 'report'): ?>
                        <div class="mb-4">
                            <h2 class="h3 fw-bold text-dark">Academic Report Card</h2>
                        </div>

                        <div class="card card-custom p-4 bg-white mb-4">
                            <form method="POST" action="dashboard.php?tab=report" class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-secondary">Class / Semester</label>
                                    <select name="report_class" class="form-select" required>
                                        <option value="">-- Choose Class Group --</option>
                                        <?php 
                                        $cl_res = $conn->query("SELECT DISTINCT email_or_class FROM users WHERE role='student' AND email_or_class != '' ORDER BY email_or_class ASC");
                                        while($cl = $cl_res->fetch_assoc()) {
                                            $selected = ($report_class === $cl['email_or_class']) ? 'selected' : '';
                                            echo "<option value='{$cl['email_or_class']}' {$selected}>{$cl['email_or_class']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-secondary text-white w-100 fw-bold">Filter Students</button>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($report_class)): ?>
                            <div class="card card-custom p-4 bg-white">
                                <h5 class="fw-bold text-primary mb-3"><?php echo $report_class; ?></h5>
                                <form method="GET" action="generate_report.php" target="_blank" class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Student</label>
                                        <select name="student_id" class="form-select" required>
                                            <option value="">-- Choose Student from <?php echo $report_class; ?> --</option>
                                            <?php 
                                            $st_res = $conn->query("SELECT id, username, symbol_no FROM users WHERE role='student' AND email_or_class = '$report_class' ORDER BY username ASC");
                                            if ($st_res && $st_res->num_rows > 0) {
                                                while($st = $st_res->fetch_assoc()) {
                                                    echo "<option value='{$st['id']}'>{$st['username']} ({$st['symbol_no']})</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-secondary">Term</label>
                                        <select name="term" class="form-select" required>
                                            <option value="">-- Choose Term --</option>
                                            <option value="1st Term">1st Term</option>
                                            <option value="2nd Term">2nd Term</option>
                                            <option value="3rd Term">3rd Term</option>
                                            <option value="Annual">Annual</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100 fw-bold">Generate Report</button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info small shadow-sm border-0">
                                Please choose a Class / Semester group first.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php else: ?>
                    <div id="student-view">
                        <div class="mb-4">
                            <h1 class="h3 fw-bold text-dark">Personal Gradesheet</h1>
                        </div>

                        <div class="card card-custom p-4 bg-white mb-4">
                            <form method="POST" action="dashboard.php" class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-secondary">Semester / Class</label>
                                    <select name="filter_class" class="form-select" required>
                                        <option value="">-- Choose Semester --</option>
                                        <?php
                                        // Pull all unique semesters that have actual grades assigned to this specific logged-in student
                                        $stud_cl_res = $conn->query("SELECT DISTINCT u.email_or_class FROM grades g JOIN users u ON g.student_id = u.id WHERE g.student_id = $user_id");
                                        if ($stud_cl_res && $stud_cl_res->num_rows > 0) {
                                            while($cl = $stud_cl_res->fetch_assoc()) {
                                                $selected = ($sel_class === $cl['email_or_class']) ? 'selected' : '';
                                                echo "<option value='{$cl['email_or_class']}' {$selected}>{$cl['email_or_class']}</option>";
                                            }
                                        } else {
                                            // Fallback default choice using the student's current registered primary profile class
                                            $profile_res = $conn->query("SELECT email_or_class FROM users WHERE id = $user_id");
                                            $prof = $profile_res->fetch_assoc();
                                            echo "<option value='{$prof['email_or_class']}'>{$prof['email_or_class']} (Current)</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Select Term</label>
                                    <select name="filter_term" class="form-select" required>
                                        <option value="">-- Choose Exam Term --</option>
                                        <option value="1st Term" <?php if($sel_term === '1st Term') echo 'selected'; ?>>1st Term</option>
                                        <option value="2nd Term" <?php if($sel_term === '2nd Term') echo 'selected'; ?>>2nd Term</option>
                                        <option value="3rd Term" <?php if($sel_term === '3rd Term') echo 'selected'; ?>>3rd Term</option>
                                        <option value="Annual" <?php if($sel_term === 'Annual') echo 'selected'; ?>>Annual</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Filter Report Card</button>
                                </div>
                            </form>
                        </div>

                        <div class="card card-custom border-0 bg-white">
                            <div class="card-body p-4">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light small text-secondary">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Term</th>
                                                <th>Percentage (%)</th>
                                                <th>GPA</th>
                                                <th class="text-center">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if (!empty($sel_class) && !empty($sel_term)):
                                                // Fetch records matching selected semester and exam term boundaries cleanly
                                                $st_sql = "SELECT subject, term, percentage, gpa, letter_grade FROM grades 
                                                           WHERE student_id = $user_id AND term = '$sel_term' ORDER BY subject ASC";
                                                $st_res = $conn->query($st_sql);
                                                if($st_res && $st_res->num_rows > 0):
                                                    while($row = $st_res->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td class="fw-semibold text-primary"><?php echo $row['subject']; ?></td>
                                                <td><?php echo $row['term']; ?></td>
                                                <td><strong><?php echo $row['percentage']; ?>%</strong></td>
                                                <td><?php echo $row['gpa']; ?></td>
                                                <td class="text-center"><span class="badge bg-success px-3 py-2"><?php echo $row['letter_grade']; ?></span></td>
                                            </tr>
                                            <?php 
                                                    endwhile;
                                                else:
                                                    echo "<tr><td colspan='5' class='text-center text-muted py-4'>No recorded transcripts found matching <strong>$sel_class ($sel_term)</strong>.</td></tr>";
                                                endif;
                                            else:
                                                echo "<tr><td colspan='5' class='text-center text-secondary py-4 fw-bold'>Please define both Semester and Exam Term above to load your transcript files cleanly.</td></tr>";
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const toast = document.getElementById('statusToast');
        const status = urlParams.get('status');

        if (status) {
            toast.classList.remove('toast-success', 'toast-danger');
            
            if (status === 'enrolled') {
                toast.innerText = "Students enrolled successfully in the subject course track!";
                toast.classList.add('toast-success', 'show');
            } else if (status === 'graded') {
                toast.innerText = "Student performance metrics saved successfully!";
                toast.classList.add('toast-success', 'show');
            } else if (status === 'updated') {
                toast.innerText = "Academic record modified successfully!";
                toast.classList.add('toast-success', 'show');
            } else if (status === 'deleted') {
                toast.innerText = "Grade entry removed from tracking ledger permanently!";
                toast.classList.add('toast-danger', 'show');
            }

            setTimeout(() => {
                toast.classList.remove('show');
                window.history.replaceState({}, document.title, window.location.pathname + (urlParams.get('tab') ? '?tab=' + urlParams.get('tab') : ''));
            }, 2500);
        }
    </script>
</body>
</html>