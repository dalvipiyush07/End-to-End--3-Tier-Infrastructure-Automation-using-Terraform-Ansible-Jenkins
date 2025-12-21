<?php
// ==============================================
// PORTFOLIO API - COMPLETE BACKEND
// ==============================================

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==============================================
// DATABASE CONFIGURATION
// ==============================================
$db_config = [
    'host' => 'ansible-3-tier-mysql.c1qcq8eoe05s.ap-south-1.rds.amazonaws.com',
    'port' => '3306',
    'name' => 'portfolio_db',
    'user' => 'admin',
    'pass' => 'piyush07'
];

// ==============================================
// UTILITY FUNCTIONS
// ==============================================
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ==============================================
// DATABASE CONNECTION
// ==============================================
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create tables if they don't exist
    createTables($pdo);
    
} catch (PDOException $e) {
    sendResponse(false, 'Database connection failed: ' . $e->getMessage(), null, 500);
}

// ==============================================
// CREATE TABLES FUNCTION
// ==============================================
function createTables($pdo) {
    // Visitors table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visitors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45),
            user_agent TEXT,
            visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            page_visited VARCHAR(255)
        )
    ");
    
    // Messages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255),
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read BOOLEAN DEFAULT FALSE
        )
    ");
    
    // Projects table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(50),
            github_url VARCHAR(500),
            demo_url VARCHAR(500),
            technologies TEXT,
            image_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert sample projects if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        insertSampleProjects($pdo);
    }
}

// ==============================================
// SAMPLE PROJECTS DATA
// ==============================================
function insertSampleProjects($pdo) {
    $sampleProjects = [
        [
            'title' => 'Jenkins CI/CD Pipeline for Node.js',
            'description' => 'End-to-end Jenkins pipeline automating build, test, and deployment of a Node.js app to EC2 with environment bootstrap scripts.',
            'category' => 'jenkins',
            'github_url' => 'https://github.com/dalvipiyush07/Jenkins-CI-CD-Pipeline-for-Node.js-Application-on-AWS-EC2.git',
            'demo_url' => '',
            'technologies' => json_encode(['Jenkins', 'AWS EC2', 'Node.js', 'PM2']),
            'image_url' => 'https://images.unsplash.com/photo-1555949963-aa79dcee981c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
        ],
        [
            'title' => '3-Tier AWS SDK Architecture',
            'description' => 'Attendance system demonstrating a 3-tier architecture using the AWS SDK.',
            'category' => 'aws',
            'github_url' => 'https://github.com/dalvipiyush07/Mark-Your-Attendance-3-Tier-AWS-SDK-Architecture-.git',
            'demo_url' => '',
            'technologies' => json_encode(['AWS VPC', 'EC2', 'RDS', 'S3', 'CloudFront']),
            'image_url' => 'https://images.unsplash.com/photo-1551650975-87deedd944c3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
        ],
        [
            'title' => 'Automated Document Processing System',
            'description' => 'Serverless file ingestion pipeline with OCR/text extraction, compression, and classification using AWS Lambda and S3.',
            'category' => 'serverless',
            'github_url' => 'https://github.com/dalvipiyush07/-Automated-Document-Processing-System-using-AWS-Serverless.git',
            'demo_url' => '',
            'technologies' => json_encode(['AWS Lambda', 'S3', 'Serverless', 'Python']),
            'image_url' => 'https://images.unsplash.com/photo-1555949963-aa79dcee981c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
        ],
        [
            'title' => 'GitLab-GitHub Repository Mirroring',
            'description' => 'Scripts and configs for mirroring projects and CI between GitLab and GitHub.',
            'category' => 'git',
            'github_url' => 'https://github.com/dalvipiyush07/Project-Mirroring-GitLab-GitHub.git',
            'demo_url' => '',
            'technologies' => json_encode(['GitLab', 'GitHub', 'Git', 'CI/CD']),
            'image_url' => 'https://images.unsplash.com/photo-1551650975-87deedd944c3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO projects (title, description, category, github_url, demo_url, technologies, image_url) 
        VALUES (:title, :description, :category, :github_url, :demo_url, :technologies, :image_url)
    ");
    
    foreach ($sampleProjects as $project) {
        $stmt->execute($project);
    }
}

// ==============================================
// ROUTE HANDLER
// ==============================================
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'test':
        handleTest();
        break;
    case 'get_projects':
        handleGetProjects($pdo);
        break;
    case 'get_skills':
        handleGetSkills();
        break;
    case 'get_visitors':
        handleGetVisitors($pdo);
        break;
    case 'send_message':
        handleSendMessage($pdo);
        break;
    case 'home':
    default:
        handleHome($pdo);
        break;
}

// ==============================================
// REQUEST HANDLERS
// ==============================================

function handleTest() {
    sendResponse(true, 'API is working', [
        'version' => '1.0',
        'status' => 'active'
    ]);
}

function handleGetProjects($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM projects 
            ORDER BY created_at DESC
        ");
        $projects = $stmt->fetchAll();
        
        // Convert technologies from JSON string to array
        foreach ($projects as &$project) {
            $project['technologies'] = json_decode($project['technologies'] ?? '[]', true);
        }
        
        sendResponse(true, 'Projects retrieved successfully', $projects);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving projects: ' . $e->getMessage());
    }
}

function handleGetSkills() {
    // Static skills data
    $skills = [
        'AWS Services' => [
            'EC2 & Auto Scaling',
            'S3 & CloudFront',
            'VPC & Networking',
            'RDS & Database',
            'Lambda',
            'IAM',
            'CloudFormation',
            'Route 53'
        ],
        'DevOps Tools' => [
            'Jenkins',
            'Docker',
            'Git & GitHub',
            'Terraform',
            'Kubernetes',
            'Ansible',
            'Prometheus',
            'Grafana'
        ],
        'Programming & Scripting' => [
            'Python',
            'Bash/Shell Scripting',
            'YAML & JSON',
            'Linux Administration',
            'SQL',
            'PowerShell',
            'REST APIs',
            'Infrastructure as Code'
        ]
    ];
    
    sendResponse(true, 'Skills retrieved successfully', $skills);
}

function handleGetVisitors($pdo) {
    try {
        // Track current visitor
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $page = $_SERVER['HTTP_REFERER'] ?? 'direct';
        
        // Insert visitor
        $stmt = $pdo->prepare("
            INSERT INTO visitors (ip_address, user_agent, page_visited) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ip, $userAgent, $page]);
        
        // Get visitor stats
        $stmt = $pdo->query("SELECT COUNT(*) as total_visitors FROM visitors");
        $total = $stmt->fetch()['total_visitors'];
        
        sendResponse(true, 'Visitor tracked successfully', [
            'total_visitors' => $total,
            'current_ip' => $ip
        ]);
        
    } catch (Exception $e) {
        // Still send success even if tracking fails
        sendResponse(true, 'API is working (visitor tracking skipped)', [
            'total_visitors' => 'N/A',
            'note' => 'Visitor tracking temporarily unavailable'
        ]);
    }
}

function handleSendMessage($pdo) {
    try {
        // Get input data
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($message)) {
            sendResponse(false, 'Please fill all required fields');
        }
        
        // Validate email
        if (!validateEmail($email)) {
            sendResponse(false, 'Please enter a valid email address');
        }
        
        // Sanitize inputs
        $name = htmlspecialchars(trim($name));
        $email = htmlspecialchars(trim($email));
        $subject = htmlspecialchars(trim($subject));
        $message = htmlspecialchars(trim($message));
        
        // Save to database
        $stmt = $pdo->prepare("
            INSERT INTO messages (name, email, subject, message) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$name, $email, $subject, $message]);
        $messageId = $pdo->lastInsertId();
        
        // Send email notification (optional - you can enable this later)
        // sendEmailNotification($name, $email, $subject, $message);
        
        sendResponse(true, 'Message sent successfully! I will get back to you soon.', [
            'message_id' => $messageId
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error sending message: ' . $e->getMessage());
    }
}

function handleHome($pdo) {
    try {
        // Get stats
        $visitorStmt = $pdo->query("SELECT COUNT(*) as count FROM visitors");
        $visitors = $visitorStmt->fetch()['count'];
        
        $messageStmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
        $messages = $messageStmt->fetch()['count'];
        
        $projectStmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
        $projects = $projectStmt->fetch()['count'];
        
        sendResponse(true, 'Portfolio API', [
            'name' => 'Piyush Dalvi',
            'title' => 'AWS & DevOps Engineer',
            'contact' => [
                'email' => 'piyushdalvi65@gmail.com',
                'phone' => '+91 9172326283',
                'location' => 'India'
            ],
            'social' => [
                'github' => 'https://github.com/dalvipiyush07',
                'linkedin' => 'https://www.linkedin.com/in/piyush-dalvi-5b1499382'
            ],
            'stats' => [
                'projects' => $projects,
                'messages' => $messages,
                'visitors' => $visitors
            ]
        ]);
        
    } catch (Exception $e) {
        sendResponse(true, 'Portfolio API is running', [
            'name' => 'Piyush Dalvi',
            'title' => 'AWS & DevOps Engineer',
            'status' => 'API is active (some features may be limited)'
        ]);
    }
}

// ==============================================
// EMAIL FUNCTION (Optional)
// ==============================================
function sendEmailNotification($name, $email, $subject, $message) {
    // Uncomment and configure if you want email notifications
    
    /*
    $to = 'piyushdalvi65@gmail.com';
    $emailSubject = "New Contact Form Message: $subject";
    $emailBody = "
        Name: $name\n
        Email: $email\n
        Subject: $subject\n\n
        Message:\n$message\n\n
        Received at: " . date('Y-m-d H:i:s') . "
    ";
    
    $headers = "From: no-reply@yourdomain.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    // Uncomment to send email
    // mail($to, $emailSubject, $emailBody, $headers);
    */
}