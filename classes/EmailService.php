<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mail;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/email.php';
        $this->mail = new PHPMailer(true);
        $this->setupSMTP();
    }

    private function setupSMTP() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['smtp_username'];
            $this->mail->Password = $this->config['smtp_password'];
            $this->mail->SMTPSecure = $this->config['smtp_encryption'];
            $this->mail->Port = $this->config['smtp_port'];

            // Recipients
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mail->addReplyTo($this->config['reply_to_email'], $this->config['reply_to_name']);
        } catch (Exception $e) {
            error_log("Email setup error: " . $e->getMessage());
        }
    }

    /**
     * Send welcome email with auto-generated password
     */
    public function sendWelcomeEmail($userEmail, $userName, $autoPassword) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($userEmail, $userName);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'Welcome to BudgetTrack - Your Account Details';

            $body = $this->getWelcomeEmailTemplate($userName, $autoPassword, $userEmail);
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset link
     */
    public function sendPasswordResetLink($userEmail, $userName, $resetToken) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($userEmail, $userName);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'BudgetTrack - Password Reset Request';

            $resetLink = "http://budgettrack-evsuocc.com/auth/reset_password.php?token=" . $resetToken;
            $body = $this->getPasswordResetEmailTemplate($userName, $resetLink);
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }

    private function getWelcomeEmailTemplate($userName, $autoPassword, $userEmail) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #800000; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .credentials { background: #fff; padding: 15px; border-left: 4px solid #800000; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; background: #800000; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to BudgetTrack</h1>
                    <p>EVSU-Ormoc Campus Budget Management System</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($userName) . "!</h2>
                    
                    <p>Your account has been successfully created in the BudgetTrack system. Below are your login credentials:</p>
                    
                    <div class='credentials'>
                        <h3>Your Account Details:</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($userEmail) . "</p>
                        <p><strong>Temporary Password:</strong> <code>" . htmlspecialchars($autoPassword) . "</code></p>
                        <p style='margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;'><strong>System URL:</strong> <a href='http://budgettrack-evsuocc.com' style='color: #800000; text-decoration: none; font-weight: bold;'>budgettrack-evsuocc.com</a></p>
                    </div>
                    
                    <div class='setup-instructions'>
                        <h3>Next Steps:</h3>
                        <ol>
                            <li><strong>Access the System:</strong> Visit <a href='http://budgettrack-evsuocc.com' style='color: #800000;'>budgettrack-evsuocc.com</a> to log in.</li>
                            <li><strong>Set Up Your Password:</strong> Click the button below or go to the login page and use the temporary password above.</li>
                            <li><strong>When prompted:</strong> enter your email and the temporary password above.</li>
                            <li><strong>Choose a new password</strong> and save. You can also use the temporary password to change later; it does not expire.</li>
                        </ol>
                    </div>
                    
                    <div class='button-container' style='text-align: center; margin: 20px 0;'>
                        <a href='http://budgettrack-evsuocc.com/pages/setup_password.php?email=" . urlencode($userEmail) . "&code=" . urlencode($autoPassword) . "' class='button'>Set Up My Password</a>
                    </div>
                    
                    <p><strong>Note:</strong> You can always return to the setup page using the same temporary password until you change it. Access the system at <a href='http://budgettrack-evsuocc.com' style='color: #800000;'>budgettrack-evsuocc.com</a></p>
                    
                    <p>If you have any questions or need assistance, please contact the Budget Office.</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from BudgetTrack System</p>
                    <p>EVSU-Ormoc Campus | Budget Office</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getPasswordResetEmailTemplate($userName, $resetLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #800000; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; background: #800000; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                    <p>BudgetTrack System</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($userName) . "!</h2>
                    
                    <p>You have requested to reset your password for your BudgetTrack account.</p>
                    
                    <p>Click the button below to reset your password:</p>
                    
                    <a href='" . $resetLink . "' class='button'>Reset Password</a>
                    
                    <p><strong>Note:</strong> This link will expire in 24 hours for security reasons.</p>
                    
                    <p>If you did not request this password reset, please ignore this email.</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from BudgetTrack System</p>
                    <p>EVSU-Ormoc Campus | Budget Office</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
