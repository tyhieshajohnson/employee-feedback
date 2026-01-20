<?php
require "db.php";
require "auth.php";
require_role("employee");

$user_id = $_SESSION["user"]["id"];
$user_name = $_SESSION["user"]["full_name"];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $department = trim($_POST["department"] ?? "");
    $feedback = trim($_POST["feedback"] ?? "");
    
    if (!empty($department) && !empty($feedback)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, department, feedback) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $department, $feedback);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Feedback submitted successfully!'
            ];
            header('Location: employee_dashboard.php');
            exit;
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to submit feedback. Please try again.'
            ];
        }
        $stmt->close();
    }
}

// Fetch employee's feedback history
$stmt = $conn->prepare("
    SELECT id, department, feedback, created_at 
    FROM feedback 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$feedback_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_feedback = count($feedback_history);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal | Feedback System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #10b981;
            --accent: #8b5cf6;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 0;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .user-details h1 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .user-details .role {
            color: var(--gray);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .logout-btn {
            background: white;
            color: var(--danger);
            border: 2px solid #fecaca;
            padding: 10px 24px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background: #fee2e2;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Stats Card */
        .stats-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-content h2 {
            font-size: 20px;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .stats-number {
            font-size: 48px;
            font-weight: 700;
            line-height: 1;
        }

        .stats-icon {
            font-size: 64px;
            opacity: 0.8;
        }

        /* Feedback Form */
        .form-section {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 40px;
            box-shadow: var(--shadow);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            color: var(--dark);
            margin-bottom: 24px;
        }

        .section-title i {
            color: var(--primary);
        }

        .feedback-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input,
        .form-group textarea {
            padding: 14px;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
            line-height: 1.5;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            align-self: flex-start;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Feedback History */
        .history-section {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        /* Feedback Cards */
        .feedback-cards {
            display: grid;
            gap: 20px;
        }

        .feedback-card {
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            padding: 24px;
            transition: var(--transition);
            position: relative;
        }

        .feedback-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .feedback-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
            border-radius: var(--radius) 0 0 var(--radius);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .department-tag {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .timestamp {
            color: var(--gray);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .feedback-content {
            color: var(--dark);
            line-height: 1.6;
            white-space: pre-wrap;
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        /* Flash Messages */
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .flash-success {
            background: var(--secondary);
            color: white;
        }

        .flash-error {
            background: #ef4444;
            color: white;
        }

        .flash-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: auto;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .logout-btn {
                width: 100%;
                justify-content: center;
            }
            
            .stats-card {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .stats-number {
                font-size: 36px;
            }
            
            .stats-icon {
                font-size: 48px;
            }
            
            .form-section,
            .history-section {
                padding: 24px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .user-avatar {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .user-details h1 {
                font-size: 24px;
            }
            
            .submit-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h1><?php echo htmlspecialchars($user_name); ?></h1>
                    <span class="role">
                        <i class="fas fa-briefcase"></i>
                        Employee Portal
                    </span>
                </div>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </header>

        <!-- Stats Card -->
        <div class="stats-card">
            <div class="stats-content">
                <h2>Your Total Feedback</h2>
                <div class="stats-number"><?php echo $total_feedback; ?></div>
            </div>
            <div class="stats-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
        </div>

        <!-- Feedback Form -->
        <section class="form-section">
            <h2 class="section-title">
                <i class="fas fa-pen-to-square"></i>
                Submit New Feedback
            </h2>
            
            <form method="POST" class="feedback-form">
                <div class="form-group">
                    <label for="department">
                        <i class="fas fa-building"></i>
                        Department
                    </label>
                    <input 
                        type="text" 
                        id="department" 
                        name="department" 
                        placeholder="Enter department name" 
                        required
                        maxlength="100"
                    >
                </div>
                
                <div class="form-group">
                    <label for="feedback">
                        <i class="fas fa-comment-medical"></i>
                        Your Feedback
                    </label>
                    <textarea 
                        id="feedback" 
                        name="feedback" 
                        placeholder="Share your thoughts, suggestions, or feedback..." 
                        required
                        maxlength="2000"
                    ></textarea>
                    <small style="color: var(--gray); text-align: right; margin-top: 4px;">
                        Max 2000 characters
                    </small>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Feedback
                </button>
            </form>
        </section>

        <!-- Feedback History -->
        <section class="history-section">
            <div class="history-header">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Your Feedback History
                </h2>
                <span class="timestamp">
                    <i class="fas fa-clock"></i>
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
            
            <?php if (empty($feedback_history)): ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <h3>No Feedback Yet</h3>
                    <p>Submit your first feedback to get started</p>
                </div>
            <?php else: ?>
                <div class="feedback-cards">
                    <?php foreach ($feedback_history as $feedback): ?>
                        <article class="feedback-card">
                            <div class="card-header">
                                <span class="department-tag">
                                    <?php echo htmlspecialchars($feedback['department']); ?>
                                </span>
                                <span class="timestamp">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?>
                                </span>
                            </div>
                            <div class="feedback-content">
                                <?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Form character counter
        document.addEventListener('DOMContentLoaded', function() {
            const feedbackTextarea = document.getElementById('feedback');
            const charCounter = document.createElement('small');
            charCounter.style.cssText = 'color: var(--gray); text-align: right; margin-top: 4px;';
            feedbackTextarea.parentNode.insertBefore(charCounter, feedbackTextarea.nextSibling);
            
            function updateCharCounter() {
                const currentLength = feedbackTextarea.value.length;
                const maxLength = 2000;
                charCounter.textContent = `${currentLength}/${maxLength} characters`;
                
                if (currentLength > maxLength * 0.9) {
                    charCounter.style.color = '#ef4444';
                } else if (currentLength > maxLength * 0.75) {
                    charCounter.style.color = '#f59e0b';
                } else {
                    charCounter.style.color = 'var(--gray)';
                }
            }
            
            feedbackTextarea.addEventListener('input', updateCharCounter);
            updateCharCounter();
            
            // Form validation
            const form = document.querySelector('.feedback-form');
            form.addEventListener('submit', function(e) {
                const department = document.getElementById('department').value.trim();
                const feedback = feedbackTextarea.value.trim();
                
                if (!department || !feedback) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (feedback.length > 2000) {
                    e.preventDefault();
                    alert('Feedback must be 2000 characters or less.');
                    return;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitBtn.disabled = true;
                
                // Re-enable button after 5 seconds if still on page (fallback)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            });
            
            // Auto-close flash messages
            setTimeout(() => {
                const flashMsg = document.querySelector('.flash-message');
                if (flashMsg) {
                    flashMsg.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => flashMsg.remove(), 300);
                }
            }, 5000);
        });
        
        // Add CSS animation for slideOut
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>