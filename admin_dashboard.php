<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'flash_messages.php';
require "db.php";
require "auth.php";
require_role("admin");

// Fetch all feedback with employee details
$sql = "
    SELECT f.id, u.full_name, u.email, f.department, f.feedback, f.created_at
    FROM feedback f
    JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
";
$result = $conn->query($sql);
$feedback = $result->fetch_all(MYSQLI_ASSOC);
$total_feedback = count($feedback);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Feedback System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php echo flash_messages_css(); ?>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --radius: 8px;
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-content h1 {
            color: var(--dark);
            font-size: 28px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-content h1 i {
            color: var(--primary);
        }

        .welcome-text {
            color: var(--gray);
            font-size: 16px;
        }

        .welcome-text strong {
            color: var(--primary);
        }

        .user-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 10px 20px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.2);
        }

        /* Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 32px;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Feedback Section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-header h2 {
            color: var(--dark);
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-state {
            background: white;
            border-radius: var(--radius);
            padding: 60px 40px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray-light);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--gray);
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: var(--gray);
        }

        /* Feedback Cards */
        .feedback-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feedback-card {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .feedback-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .user-info h4 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .user-info .email {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .meta-info {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .department {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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
            margin-bottom: 20px;
            padding: 16px;
            background: var(--light);
            border-radius: 6px;
            white-space: pre-wrap;
        }

        .feedback-actions {
            display: flex;
            justify-content: flex-end;
        }

        .delete-form {
            display: inline;
        }

        .delete-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delete-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .header-content h1 {
                font-size: 24px;
            }
            
            .feedback-card {
                padding: 20px;
            }
            
            .stat-number {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-actions {
                width: 100%;
            }
            
            .logout-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php display_flash_message(); ?>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
                <p class="welcome-text">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION["user"]["full_name"]); ?></strong>
                </p>
            </div>
            <div class="user-actions">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </header>

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-comments"></i>
                <div class="stat-number"><?php echo $total_feedback; ?></div>
                <div class="stat-label">Total Feedback</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number">
                    <?php
                    $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                    echo $userCount;
                    ?>
                </div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <div class="stat-number">
                    <?php
                    $deptCount = $conn->query("SELECT COUNT(DISTINCT department) as count FROM feedback")->fetch_assoc()['count'];
                    echo $deptCount;
                    ?>
                </div>
                <div class="stat-label">Departments</div>
            </div>
        </div>

        <!-- Feedback Section -->
        <section class="feedback-section">
            <div class="section-header">
                <h2><i class="fas fa-inbox"></i> All Feedback</h2>
                <div class="meta-info">
                    <span class="timestamp">
                        <i class="fas fa-clock"></i>
                        <?php echo date('F j, Y'); ?>
                    </span>
                </div>
            </div>

            <?php if (empty($feedback)): ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <h3>No Feedback Yet</h3>
                    <p>Feedback submitted by users will appear here</p>
                </div>
            <?php else: ?>
                <div class="feedback-container">
                    <?php foreach ($feedback as $item): ?>
                        <article class="feedback-card">
                            <div class="feedback-header">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($item["full_name"]); ?></h4>
                                    <p class="email"><?php echo htmlspecialchars($item["email"]); ?></p>
                                    <div class="meta-info">
                                        <span class="department"><?php echo htmlspecialchars($item["department"]); ?></span>
                                        <span class="timestamp">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($item["created_at"])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="feedback-content">
                                <?php echo nl2br(htmlspecialchars($item["feedback"])); ?>
                            </div>
                            
                            <div class="feedback-actions">
                                <form method="POST" action="delete_feedback.php" 
                                      onsubmit="return confirm('Are you sure you want to delete this feedback?');"
                                      class="delete-form">
                                    <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
                                    <button type="submit" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                        Delete Feedback
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Add smooth scrolling and confirm dialogs
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to cards
            const cards = document.querySelectorAll('.feedback-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Enhanced delete confirmation
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('⚠️ Warning: This action cannot be undone.\n\nDelete this feedback permanently?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>