<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/UserActivity.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/FileSubmission.php';

class ReportGenerator {
    private $conn;
    private $activityLogger;
    private $notification;
    private $fileSubmission;
    
    public function __construct() {
        $this->conn = getDB();
        $this->activityLogger = new UserActivity();
        $this->notification = new Notification();
        $this->fileSubmission = new FileSubmission();
    }
    
    /**
     * Generate a comprehensive activity report
     */
    public function generateReport($reportType, $periodStart, $periodEnd, $generatedBy = null) {
        // Get all activities for the period
        $activities = $this->getActivitiesForPeriod($periodStart, $periodEnd);
        
        // Get all notifications for the period
        $notifications = $this->getNotificationsForPeriod($periodStart, $periodEnd);
        
        // Get all file submissions for the period
        $submissions = $this->getSubmissionsForPeriod($periodStart, $periodEnd);
        
        // Generate PDF
        $pdfContent = $this->generatePDF($reportType, $periodStart, $periodEnd, $activities, $notifications, $submissions);
        
        // Save file
        $fileName = $this->saveReportFile($pdfContent, $reportType, $periodStart, $periodEnd);
        
        // Save to database
        $reportId = $this->saveReportRecord($reportType, $periodStart, $periodEnd, $fileName, $generatedBy);
        
        return [
            'id' => $reportId,
            'file_name' => $fileName,
            'file_path' => 'uploads/reports/' . $fileName
        ];
    }
    
    /**
     * Get activities for a specific period
     */
    private function getActivitiesForPeriod($startDate, $endDate) {
        $query = "SELECT ual.*, u.first_name, u.last_name, u.email, d.dept_name, r.role_name
                  FROM user_activity_log ual
                  LEFT JOIN users u ON ual.user_id = u.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE DATE(ual.created_at) BETWEEN :start_date AND :end_date
                  ORDER BY ual.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get notifications for a specific period
     */
    private function getNotificationsForPeriod($startDate, $endDate) {
        $query = "SELECT n.*, u.first_name, u.last_name, u.email
                  FROM notifications n
                  LEFT JOIN users u ON n.user_id = u.id
                  WHERE DATE(n.created_at) BETWEEN :start_date AND :end_date
                  ORDER BY n.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get file submissions for a specific period
     */
    private function getSubmissionsForPeriod($startDate, $endDate) {
        $query = "SELECT fs.*, u.first_name, u.last_name, u.email, d.dept_name
                  FROM file_submissions fs
                  LEFT JOIN users u ON fs.user_id = u.id
                  LEFT JOIN departments d ON fs.department_id = d.id
                  WHERE DATE(fs.submitted_at) BETWEEN :start_date AND :end_date
                  ORDER BY fs.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate PDF content using HTML to PDF conversion
     */
    private function generatePDF($reportType, $periodStart, $periodEnd, $activities, $notifications, $submissions) {
        // Create HTML content for PDF
        $html = $this->generateHTMLReport($reportType, $periodStart, $periodEnd, $activities, $notifications, $submissions);
        
        // For now, return HTML - we'll use a JavaScript-based PDF generator on the frontend
        // or implement server-side PDF generation later
        return $html;
    }
    
    /**
     * Generate HTML report content
     */
    private function generateHTMLReport($reportType, $periodStart, $periodEnd, $activities, $notifications, $submissions) {
        $periodLabel = ucfirst($reportType) . ' Report';
        $periodRange = date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd));
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo htmlspecialchars($periodLabel); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #800000; border-bottom: 3px solid #800000; padding-bottom: 10px; }
                h2 { color: #5a0000; margin-top: 30px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #800000; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .summary { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .summary-item { margin: 5px 0; }
            </style>
        </head>
        <body>
            <h1><?php echo htmlspecialchars($periodLabel); ?></h1>
            <p><strong>Period:</strong> <?php echo htmlspecialchars($periodRange); ?></p>
            <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
            
            <div class="summary">
                <h2>Summary</h2>
                <div class="summary-item"><strong>Total Activities:</strong> <?php echo count($activities); ?></div>
                <div class="summary-item"><strong>Total Notifications:</strong> <?php echo count($notifications); ?></div>
                <div class="summary-item"><strong>Total Submissions:</strong> <?php echo count($submissions); ?></div>
            </div>
            
            <h2>Activities</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Department</th>
                        <th>Activity Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars(trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''))); ?></td>
                        <td><?php echo htmlspecialchars($activity['dept_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($this->activityLogger->formatActivityType($activity['activity_type'])); ?></td>
                        <td><?php 
                            $details = json_decode($activity['activity_details'] ?? '{}', true);
                            echo htmlspecialchars(is_array($details) ? json_encode($details, JSON_UNESCAPED_SLASHES) : ($activity['activity_details'] ?? ''));
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>Notifications</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                    <tr>
                        <td><?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars(trim(($notification['first_name'] ?? '') . ' ' . ($notification['last_name'] ?? ''))); ?></td>
                        <td><?php echo htmlspecialchars($notification['title']); ?></td>
                        <td><?php echo htmlspecialchars($notification['message']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($notification['type'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>File Submissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?php echo date('M d, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                        <td><?php echo htmlspecialchars(trim(($submission['first_name'] ?? '') . ' ' . ($submission['last_name'] ?? ''))); ?></td>
                        <td><?php echo htmlspecialchars($submission['dept_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($submission['submission_type']); ?></td>
                        <td><?php echo htmlspecialchars($submission['file_name']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($submission['status'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Save report file
     */
    private function saveReportFile($content, $reportType, $periodStart, $periodEnd) {
        $uploadDir = __DIR__ . '/../uploads/reports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = 'report_' . $reportType . '_' . str_replace('-', '', $periodStart) . '_' . str_replace('-', '', $periodEnd) . '_' . time() . '.html';
        $filePath = $uploadDir . $fileName;
        
        file_put_contents($filePath, $content);
        
        return $fileName;
    }
    
    /**
     * Save report record to database
     */
    private function saveReportRecord($reportType, $periodStart, $periodEnd, $fileName, $generatedBy) {
        $filePath = 'uploads/reports/' . $fileName;
        $fileSize = filesize(__DIR__ . '/../' . $filePath);
        
        $query = "INSERT INTO automated_reports 
                  (report_type, report_period_start, report_period_end, file_name, file_path, file_size, generated_by)
                  VALUES (:report_type, :period_start, :period_end, :file_name, :file_path, :file_size, :generated_by)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':report_type', $reportType);
        $stmt->bindParam(':period_start', $periodStart);
        $stmt->bindParam(':period_end', $periodEnd);
        $stmt->bindParam(':file_name', $fileName);
        $stmt->bindParam(':file_path', $filePath);
        $stmt->bindParam(':file_size', $fileSize);
        $stmt->bindParam(':generated_by', $generatedBy);
        $stmt->execute();
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Get all reports with optional filter
     */
    public function getReports($reportType = null, $limit = 50) {
        $query = "SELECT ar.*, u.first_name, u.last_name
                  FROM automated_reports ar
                  LEFT JOIN users u ON ar.generated_by = u.id";
        
        if ($reportType) {
            $query .= " WHERE ar.report_type = :report_type";
        }
        
        $query .= " ORDER BY ar.generated_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        if ($reportType) {
            $stmt->bindParam(':report_type', $reportType);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get report by ID
     */
    public function getReportById($reportId) {
        $query = "SELECT ar.*, u.first_name, u.last_name
                  FROM automated_reports ar
                  LEFT JOIN users u ON ar.generated_by = u.id
                  WHERE ar.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

