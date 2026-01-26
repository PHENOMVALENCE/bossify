<?php
/**
 * Email Service Class
 * Handles email sending using PHPMailer
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;
    private $recipient_email;
    
    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_user = SMTP_USER;
        $this->smtp_pass = SMTP_PASS;
        $this->from_email = SMTP_FROM_EMAIL;
        $this->from_name = SMTP_FROM_NAME;
        $this->recipient_email = ADMIN_EMAIL; // All emails go to mwiganivalence@gmail.com
    }
    
    /**
     * Initialize PHPMailer with SMTP settings
     */
    private function initMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_user;
            $mail->Password = $this->smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Sender
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addReplyTo(SITE_EMAIL, SITE_NAME);
            
            return $mail;
        } catch (Exception $e) {
            error_log("PHPMailer initialization error: " . $mail->ErrorInfo);
            return null;
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    public function send($to, $subject, $message, $isHTML = true) {
        $mail = $this->initMailer();
        if (!$mail) {
            return false;
        }
        
        try {
            // Recipient - always send to mwiganivalence@gmail.com
            $mail->addAddress($this->recipient_email);
            
            // If different recipient specified, add as CC
            if ($to !== $this->recipient_email && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($to);
            }
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send contact form notification
     */
    public function sendContactNotification($data) {
        $subject = "New Contact Form Submission: " . $data['subject'];
        $message = $this->getContactEmailTemplate($data);
        
        // Send to admin (mwiganivalence@gmail.com)
        $result = $this->send($this->recipient_email, $subject, $message);
        
        // Send auto-reply to user
        if ($result) {
            $this->sendContactAutoReply($data['email'], $data['name']);
        }
        
        return $result;
    }
    
    /**
     * Send enrollment confirmation
     */
    public function sendEnrollmentConfirmation($data) {
        $subject = "Enrollment Application Received - Bossify Academy";
        $message = $this->getEnrollmentEmailTemplate($data);
        
        // Send confirmation to user
        $this->send($data['email'], $subject, $message);
        
        // Notify admin
        $adminSubject = "New Enrollment Application: " . $data['first_name'] . " " . $data['last_name'];
        $adminMessage = $this->getEnrollmentAdminTemplate($data);
        $this->send($this->recipient_email, $adminSubject, $adminMessage);
    }
    
    /**
     * Send newsletter welcome email
     */
    public function sendNewsletterWelcome($email) {
        $subject = "Welcome to Bossify Academy Newsletter";
        $message = $this->getNewsletterWelcomeTemplate();
        
        // Send welcome email to subscriber
        $result = $this->send($email, $subject, $message);
        
        // Notify admin of new subscription
        if ($result) {
            $adminSubject = "New Newsletter Subscription: " . $email;
            $adminMessage = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4B2C5E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Newsletter Subscription</h2>
                    </div>
                    <div class='content'>
                        <p>A new subscriber has joined the newsletter:</p>
                        <p><strong>Email:</strong> {$email}</p>
                        <p><strong>Date:</strong> " . date('F d, Y H:i') . "</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $this->send($this->recipient_email, $adminSubject, $adminMessage);
        }
        
        return $result;
    }
    
    private function getContactEmailTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4B2C5E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
                .field { margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px; }
                .label { font-weight: bold; color: #4B2C5E; display: block; margin-bottom: 5px; }
                .value { color: #333; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Contact Form Submission</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <span class='label'>Name:</span>
                        <span class='value'>{$data['name']}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Email:</span>
                        <span class='value'>{$data['email']}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Subject:</span>
                        <span class='value'>{$data['subject']}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Message:</span>
                        <div class='value'>" . nl2br(htmlspecialchars($data['message'])) . "</div>
                    </div>
                    <p style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;'>
                        This email was sent from the Bossify Academy contact form.
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getEnrollmentEmailTemplate($data) {
        $packageNames = [
            'single_track' => 'Single Track Program',
            'full_cohort' => 'Complete Cohort Program',
            'corporate' => 'Corporate Partnership'
        ];
        
        $packageName = $packageNames[$data['package_type']] ?? $data['package_type'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #D4AF37 0%, #CD7F32 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Enrollment Application Received</h2>
                </div>
                <div class='content'>
                    <p>Dear {$data['first_name']},</p>
                    <p>Thank you for your interest in Bossify Academy! We have received your enrollment application for the <strong>{$packageName}</strong>.</p>
                    <p>Our team will review your application and get back to you within 2-3 business days.</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='color: #4B2C5E; margin-top: 0;'>Application Details:</h3>
                        <p><strong>Name:</strong> {$data['first_name']} {$data['last_name']}</p>
                        <p><strong>Email:</strong> {$data['email']}</p>
                        <p><strong>Package:</strong> {$packageName}</p>
                        <p><strong>Application ID:</strong> #{$data['id']}</p>
                    </div>
                    <p>Best regards,<br><strong>Bossify Academy Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getEnrollmentAdminTemplate($data) {
        $packageNames = [
            'single_track' => 'Single Track Program',
            'full_cohort' => 'Complete Cohort Program',
            'corporate' => 'Corporate Partnership'
        ];
        
        $packageName = $packageNames[$data['package_type']] ?? $data['package_type'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4B2C5E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
                .field { margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px; }
                .label { font-weight: bold; color: #4B2C5E; display: block; margin-bottom: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Enrollment Application</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <span class='label'>Name:</span> {$data['first_name']} {$data['last_name']}
                    </div>
                    <div class='field'>
                        <span class='label'>Email:</span> {$data['email']}
                    </div>
                    <div class='field'>
                        <span class='label'>Phone:</span> {$data['phone']}
                    </div>
                    <div class='field'>
                        <span class='label'>Package:</span> {$packageName}
                    </div>";
        
        if (!empty($data['course_track'])) {
            $message .= "<div class='field'><span class='label'>Course Track:</span> " . ucfirst(str_replace('_', ' ', $data['course_track'])) . "</div>";
        }
        
        if (!empty($data['company_name'])) {
            $message .= "<div class='field'><span class='label'>Company:</span> {$data['company_name']}</div>";
        }
        
        if (!empty($data['position'])) {
            $message .= "<div class='field'><span class='label'>Position:</span> {$data['position']}</div>";
        }
        
        $message .= "
                    <div class='field'>
                        <span class='label'>Motivation:</span>
                        <div>" . nl2br(htmlspecialchars($data['motivation'])) . "</div>
                    </div>
                    <div class='field'>
                        <span class='label'>Payment Method:</span> " . ucfirst(str_replace('_', ' ', $data['payment_method'])) . "
                    </div>
                    <div class='field'>
                        <span class='label'>Application ID:</span> #{$data['id']}
                    </div>
                    <p style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;'>
                        Please review this application in the admin dashboard.
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        return $message;
    }
    
    private function getNewsletterWelcomeTemplate() {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #D4AF37 0%, #CD7F32 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to Bossify Academy Newsletter!</h2>
                </div>
                <div class='content'>
                    <p>Thank you for subscribing to our newsletter. You'll receive updates about:</p>
                    <ul>
                        <li>New cohort openings</li>
                        <li>Leadership insights and tips</li>
                        <li>Upcoming events and workshops</li>
                        <li>Success stories from our alumni</li>
                    </ul>
                    <p>We're excited to have you as part of our community!</p>
                    <p>Best regards,<br><strong>Bossify Academy Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function sendContactAutoReply($email, $name) {
        $subject = "Thank you for contacting Bossify Academy";
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #D4AF37 0%, #CD7F32 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thank You for Contacting Us</h2>
                </div>
                <div class='content'>
                    <p>Dear {$name},</p>
                    <p>Thank you for reaching out to Bossify Academy. We have received your message and will respond within 24-48 hours.</p>
                    <p>Best regards,<br><strong>Bossify Academy Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
        
        $this->send($email, $subject, $message);
    }
}
