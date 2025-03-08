<footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Về chúng tôi</h5>
                    <p><?= htmlspecialchars($settings['site_title'] ?? 'Ecommerce') ?> - <?= htmlspecialchars($settings['site_description'] ?? 'Nền tảng mua sắm trực tuyến hàng đầu') ?></p>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($settings['contact_email'] ?? '') ?></li>
                        <li><i class="fas fa-phone me-2"></i><?= htmlspecialchars($settings['phone_number'] ?? '') ?></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Kết nối với chúng tôi</h5>
                    <div class="social-links">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?= $settings['facebook_url'] ?>" class="text-white me-2"><i class="fab fa-facebook"></i></a>
                        <?php endif ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?= $settings['instagram_url'] ?>" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <?php endif ?>
                        <?php if (!empty($settings['youtube_url'])): ?>
                            <a href="<?= $settings['youtube_url'] ?>" class="text-white me-2"><i class="fab fa-youtube"></i></a>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>