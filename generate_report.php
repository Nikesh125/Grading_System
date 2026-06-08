<?php
require_once 'db.php';
session_start();

// Security Guard: Prevent non-teachers from rendering reports
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.html");
    exit();
}

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';

// Fetch targeted student profile metrics details
$user_sql = "SELECT username, symbol_no, email_or_class FROM users WHERE id = $student_id AND role = 'student'";
$user_res = $conn->query($user_sql);

if (!$user_res || $user_res->num_rows === 0) {
    die("<h3 style='text-align:center; margin-top:50px;'>Error: Student profile records not initialized or mismatch detected.</h3>");
}

$student = $user_res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official_Report_Card_<?php echo $student['symbol_no']; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #ffffff;
            color: #000000;
            margin: 0;
            padding: 40px;
        }
        .report-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000000;
            padding: 30px;
            background: #ffffff;
        }
        .header-title {
            text-align: center;
            text-transform: uppercase;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .header-subtitle {
            text-align: center;
            font-size: 14px;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .meta-info-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .meta-info-table td {
            padding: 6px;
            font-size: 15px;
        }
        .meta-info-table td strong {
            text-transform: uppercase;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .report-table th, .report-table td {
            border: 1px solid #000000;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        .report-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }
        .footer-signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000000;
            text-align: center;
            padding-top: 5px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn {
            background-color: #000000;
            color: #ffffff;
            border: none;
            padding: 10px 25px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 13px;
            border-radius: 4px;
        }
        
        /* CSS Print Media Overrides */
        @media print {
            .print-btn-container {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .report-container {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button class="print-btn" onclick="window.print()">Print This Report Card</button>
    </div>

    <div class="report-container">
        <div class="header-title">Students' Grade Management System</div>
        <div class="header-subtitle">Student's Academic Report Card</div>

        <table class="meta-info-table">
            <tr>
                <td width="18%">Student Name:</td>
                <td width="42%"><strong><?php echo htmlspecialchars($student['username']); ?></strong></td>
                <td width="15%">Term Scale:</td>
                <td width="25%"><strong><?php echo htmlspecialchars($term); ?></strong></td>
            </tr>
            <tr>
                <td>Symbol No:</td>
                <td><strong><?php echo htmlspecialchars($student['symbol_no']); ?></strong></td>
                <td>Academic Year:</td>
                <td><strong><?php echo date('Y'); ?></strong></td>
            </tr>
            <tr>
                <td>Class Group:</td>
                <td colspan="3"><strong><?php echo htmlspecialchars($student['email_or_class']); ?></strong></td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Subjects</th>
                    <th style="width: 20%;">Percentage (%)</th>
                    <th style="width: 15%;">GPA</th>
                    <th style="width: 15%;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grades_sql = "SELECT subject, percentage, gpa, letter_grade FROM grades 
                               WHERE student_id = $student_id AND term = '$term' ORDER BY subject ASC";
                $grades_res = $conn->query($grades_sql);

                if ($grades_res && $grades_res->num_rows > 0):
                    $total_percentage = 0;
                    $count = 0;
                    while ($row = $grades_res->fetch_assoc()):
                        $total_percentage += $row['percentage'];
                        $count++;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                    <td><?php echo number_format($row['percentage'], 2); ?>%</td>
                    <td><?php echo number_format($row['gpa'], 2); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['letter_grade']); ?></strong></td>
                </tr>
                <?php 
                    endwhile;
                    
                    $average_percentage = $total_percentage / $count;
                    $final_gpa = ($average_percentage / 100) * 4.00;
                    
                    if ($average_percentage >= 90)       { $final_letter = "A+"; }
                    elseif ($average_percentage >= 80) { $final_letter = "A";  }
                    elseif ($average_percentage >= 70) { $final_letter = "B+"; }
                    elseif ($average_percentage >= 60) { $final_letter = "B";  }
                    elseif ($average_percentage >= 50) { $final_letter = "C+"; }
                    elseif ($average_percentage >= 40) { $final_letter = "C";  }
                    else { $final_letter = "NG"; $final_gpa = 0.00; }
                ?>
                <tr style="background-color: #fafafa; font-weight: bold;">
                    <td>AVERAGE SUMMARY METRICS</td>
                    <td><?php echo number_format($average_percentage, 2); ?>%</td>
                    <td><?php echo number_format($final_gpa, 2); ?></td>
                    <td><span style="border: 1px solid #000; padding: 2px 8px;"><?php echo $final_letter; ?></span></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #555; padding: 20px;">
                        No validated grade logs currently registered for this profile index within the chosen term.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-signatures">
            <div class="signature-line">Principal</div>
            <div class="signature-line">Seal of Institute</div>
        </div>
    </div>

</body>
</html>