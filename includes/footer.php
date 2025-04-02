<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> Golden Crop Distributors Ltd. All rights reserved.</p>
        <div class="footer-links">
            <a href="#" class="text-white">Privacy Policy</a> |
            <a href="#" class="text-white">Terms of Service</a> |
            <a href="#" class="text-white">Contact Us</a>
        </div>
    </div>
</footer>

<style>
.footer {
    background: linear-gradient(135deg, #000080, #000066);
    padding: 20px;
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    font-size: 0.9rem;
}

.footer-links a {
    color: white;
    text-decoration: none;
    margin: 0 10px;
}

.footer-links a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
    .footer-links { margin-top: 10px; }
    .footer-links a { margin: 0 5px; }
}
</style>

</body>
</html>