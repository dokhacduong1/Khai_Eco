<footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h5 class="text-lg font-semibold mb-4">Về chúng tôi</h5>
                <p><?= htmlspecialchars($settings['site_title'] ?? 'Ecommerce') ?> - <?= htmlspecialchars($settings['site_description'] ?? 'Nền tảng mua sắm trực tuyến hàng đầu') ?></p>
            </div>
            <div>
                <h5 class="text-lg font-semibold mb-4">Liên hệ</h5>
                <ul class="list-none space-y-2">
                    <li><i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($settings['contact_email'] ?? '') ?></li>
                    <li><i class="fas fa-phone mr-2"></i><?= htmlspecialchars($settings['phone_number'] ?? '') ?></li>
                </ul>
            </div>
            <div>
                <h5 class="text-lg font-semibold mb-4">Kết nối với chúng tôi</h5>
                <div class="flex space-x-4">
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= $settings['facebook_url'] ?>" class="text-white hover:text-gray-400"><i class="fab fa-facebook fa-lg"></i></a>
                    <?php endif ?>
                    <?php if (!empty($settings['instagram_url'])): ?>
                        <a href="<?= $settings['instagram_url'] ?>" class="text-white hover:text-gray-400"><i class="fab fa-instagram fa-lg"></i></a>
                    <?php endif ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= $settings['youtube_url'] ?>" class="text-white hover:text-gray-400"><i class="fab fa-youtube fa-lg"></i></a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</footer>