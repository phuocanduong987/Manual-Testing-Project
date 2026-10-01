<?php
/**
 * Hàm tiện ích hiển thị (UI only) — không liên quan tới các lỗi bảo mật cố ý
 * của đề tài, chỉ để chọn icon minh hoạ theo tên sản phẩm.
 *
 * Icon được vẽ bằng SVG tự thiết kế (không phải emoji hệ thống) để đảm bảo
 * hiển thị đồng nhất, sắc nét và có màu trên mọi trình duyệt/hệ điều hành.
 */

/**
 * Trả về mảng ['shape','bg','fg'] dựa theo tên sản phẩm.
 */
function product_visual_meta(string $name): array {
    $name = mb_strtolower($name);
    $map = [
        'bàn phím' => ['keyboard', '#e7e4fb', '#5643b8'],
        'chuột'    => ['mouse',    '#dcf5f1', '#0d8f85'],
        'tai nghe' => ['headphone','#fde3ec', '#db2777'],
        'bàn di'   => ['pad',      '#fef3d6', '#b45309'],
        'webcam'   => ['webcam',   '#dcf0fd', '#0284c7'],
        'loa'      => ['speaker',  '#ede4fd', '#7c3aed'],
        'ổ cứng'   => ['disk',     '#e7e9f2', '#475569'],
        'usb'      => ['disk',     '#e7e9f2', '#475569'],
        'màn hình' => ['monitor',  '#d7f6f6', '#0891b2'],
        'giá đỡ'   => ['stand',    '#eef7d6', '#65a30d'],
        'ghế'      => ['chair',    '#fde8d6', '#ea580c'],
        'đèn'      => ['lamp',     '#fef6d0', '#ca8a04'],
        'balo'     => ['backpack', '#d7f7e6', '#059669'],
        'túi'      => ['backpack', '#d7f7e6', '#059669'],
        'sạc'      => ['charger',  '#fbe3f7', '#c026d3'],
        'cáp'      => ['cable',    '#ececf4', '#52525b'],
        'micro'    => ['mic',      '#fde3e3', '#dc2626'],
        'bàn'      => ['desk',     '#fdeee0', '#d97706'],
        'kính'     => ['glasses',  '#e3ecfa', '#2563eb'],
        'pin'      => ['battery',  '#e7f6ec', '#16a34a'],
    ];
    foreach ($map as $needle => $v) {
        if (mb_strpos($name, $needle) !== false) {
            return ['shape' => $v[0], 'bg' => $v[1], 'fg' => $v[2]];
        }
    }
    return ['shape' => 'bag', 'bg' => '#e7e4fb', 'fg' => '#5643b8'];
}

function product_icon_path(string $shape): string {
    switch ($shape) {
        case 'keyboard':
            return '<rect x="3" y="7" width="18" height="10" rx="1.6"/>'
                 . '<rect x="5.5" y="9.3" width="1.6" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="8.2" y="9.3" width="1.6" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="10.9" y="9.3" width="1.6" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="13.6" y="9.3" width="1.6" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="16.3" y="9.3" width="1.2" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="5.5" y="12.4" width="1.6" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="8.2" y="12.4" width="1.6" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="10.9" y="12.4" width="4.9" height="1.6" fill="currentColor" stroke="none"/>'
                 . '<rect x="16.3" y="12.4" width="1.2" height="1.6" fill="currentColor" stroke="none"/>';
        case 'mouse':
            return '<rect x="7.5" y="3" width="9" height="18" rx="4.5"/><line x1="12" y1="3" x2="12" y2="9"/>';
        case 'headphone':
            return '<path d="M4 14.5v-2a8 8 0 0 1 16 0v2"/>'
                 . '<rect x="2" y="14.5" width="5" height="7" rx="2"/>'
                 . '<rect x="17" y="14.5" width="5" height="7" rx="2"/>';
        case 'pad':
            return '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 14h18" stroke-dasharray="2.2 2.2"/>';
        case 'webcam':
            return '<circle cx="12" cy="10" r="6"/><circle cx="12" cy="10" r="2.2" fill="currentColor" stroke="none"/>'
                 . '<rect x="9" y="16.3" width="6" height="2.6" rx="1"/>';
        case 'speaker':
            return '<rect x="6.5" y="2" width="11" height="20" rx="2.4"/><circle cx="12" cy="7.6" r="1.9"/><circle cx="12" cy="15" r="3.2"/>';
        case 'disk':
            return '<rect x="3" y="7" width="18" height="10" rx="2"/><circle cx="7" cy="12" r="1" fill="currentColor" stroke="none"/><line x1="10" y1="12" x2="18" y2="12"/>';
        case 'monitor':
            return '<rect x="3" y="4" width="18" height="12" rx="2"/><line x1="8" y1="20" x2="16" y2="20"/><line x1="12" y1="16" x2="12" y2="20"/>';
        case 'stand':
            return '<path d="M4 18l8-11 8 11"/><line x1="3" y1="18" x2="21" y2="18"/>';
        case 'chair':
            return '<rect x="6" y="3" width="12" height="7.5" rx="1.4"/><rect x="5" y="10.5" width="14" height="4" rx="1.2"/>'
                 . '<line x1="7" y1="14.5" x2="7" y2="21"/><line x1="17" y1="14.5" x2="17" y2="21"/>';
        case 'lamp':
            return '<path d="M8.2 4h7.6l-1.8 6h-4z"/><line x1="12" y1="10" x2="12" y2="18"/><line x1="7.5" y1="20.5" x2="16.5" y2="20.5"/>';
        case 'backpack':
            return '<rect x="5" y="7.5" width="14" height="13.5" rx="3"/><path d="M8.2 7.5V5.3a3.8 3.8 0 0 1 7.6 0v2.2"/>'
                 . '<rect x="9" y="11.5" width="6" height="4" rx="1"/>';
        case 'charger':
            return '<rect x="7" y="3" width="10" height="7" rx="1.5"/><line x1="10" y1="10" x2="10" y2="13.5"/><line x1="14" y1="10" x2="14" y2="13.5"/>'
                 . '<path d="M9 13.5h6l-1 7.5h-4z"/>';
        case 'cable':
            return '<path d="M4 6.5c4.2 0 4.2 11 8 11s3.8-11 8-11"/>'
                 . '<rect x="2" y="4.3" width="4" height="4" rx="1"/><rect x="18" y="4.3" width="4" height="4" rx="1"/>';
        case 'mic':
            return '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5.2 11a6.8 6.8 0 0 0 13.6 0"/>'
                 . '<line x1="12" y1="17.8" x2="12" y2="22"/><line x1="8" y1="22" x2="16" y2="22"/>';
        case 'desk':
            return '<line x1="3" y1="8" x2="21" y2="8"/><line x1="3" y1="8" x2="3" y2="19"/><line x1="21" y1="8" x2="21" y2="19"/>'
                 . '<line x1="6" y1="12" x2="6" y2="16"/><line x1="18" y1="12" x2="18" y2="16"/>';
        case 'glasses':
            return '<circle cx="7" cy="13" r="3.4"/><circle cx="17" cy="13" r="3.4"/><line x1="10.4" y1="12" x2="13.6" y2="12"/><line x1="3.6" y1="11.5" x2="1.5" y2="10"/><line x1="20.4" y1="11.5" x2="22.5" y2="10"/>';
        case 'battery':
            return '<rect x="3" y="8" width="16" height="8" rx="1.6"/><rect x="19.5" y="10.5" width="2" height="3" fill="currentColor" stroke="none"/><rect x="6" y="10.5" width="8" height="3" fill="currentColor" stroke="none"/>';
        default: // bag / generic
            return '<path d="M6.5 8h11l-1 12.5h-9z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>';
    }
}

/** Trộn màu hex với trắng (percent 0-100) để ra tông nhạt hơn. */
function hex_lighten(string $hex, int $percent): string {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $p = max(0, min(100, $percent)) / 100;
    $r = (int) round($r + (255 - $r) * $p);
    $g = (int) round($g + (255 - $g) * $p);
    $b = (int) round($b + (255 - $b) * $p);
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/** Trộn màu hex với đen (percent 0-100) để ra tông đậm hơn (dùng làm bóng đổ). */
function hex_darken(string $hex, int $percent): string {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $p = max(0, min(100, $percent)) / 100;
    $r = (int) round($r * (1 - $p));
    $g = (int) round($g * (1 - $p));
    $b = (int) round($b * (1 - $p));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Vẽ hình minh hoạ chi tiết (nhiều mảng màu, có bóng/điểm sáng) cho từng loại
 * sản phẩm, viewBox 64x64 — mô phỏng dáng sản phẩm thật thay vì icon nét đơn.
 * $fg: màu chủ đạo. Trả về nội dung bên trong thẻ <svg>.
 */
function product_illustration_svg(string $shape, string $fg): string {
    $dark  = hex_darken($fg, 25);
    $mid   = hex_lighten($fg, 35);
    $light = hex_lighten($fg, 70);
    $shadow = '<ellipse cx="32" cy="55" rx="21" ry="3.4" fill="#1a1530" opacity="0.10"/>';

    switch ($shape) {
        case 'keyboard':
            return $shadow
                 . '<rect x="7" y="21" width="50" height="27" rx="5" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="7" y="21" width="50" height="8" rx="5" fill="' . $light . '" opacity="0.7"/>'
                 . '<g fill="' . $fg . '">'
                 . '<rect x="12" y="26" width="5" height="5" rx="1.2"/><rect x="19" y="26" width="5" height="5" rx="1.2"/><rect x="26" y="26" width="5" height="5" rx="1.2"/><rect x="33" y="26" width="5" height="5" rx="1.2"/><rect x="40" y="26" width="5" height="5" rx="1.2"/><rect x="47" y="26" width="5" height="5" rx="1.2"/>'
                 . '<rect x="12" y="33" width="5" height="5" rx="1.2"/><rect x="19" y="33" width="5" height="5" rx="1.2"/><rect x="26" y="33" width="5" height="5" rx="1.2"/><rect x="33" y="33" width="5" height="5" rx="1.2"/><rect x="40" y="33" width="5" height="5" rx="1.2"/><rect x="47" y="33" width="5" height="5" rx="1.2"/>'
                 . '<rect x="16" y="40" width="32" height="5" rx="1.6"/>'
                 . '</g>';
        case 'mouse':
            return $shadow
                 . '<path d="M32 12c9 0 14 7 14 17v10c0 8-6 13-14 13s-14-5-14-13V29c0-10 5-17 14-17z" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<path d="M32 12v18" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<path d="M25 13.5c-4 3-7 8.5-7 15.5" stroke="' . $light . '" stroke-width="2.4" fill="none" opacity="0.8"/>'
                 . '<rect x="29" y="17" width="6" height="8" rx="3" fill="' . $fg . '"/>';
        case 'headphone':
            return $shadow
                 . '<path d="M13 34v-3c0-11 8.5-19 19-19s19 8 19 19v3" fill="none" stroke="' . $dark . '" stroke-width="4" stroke-linecap="round"/>'
                 . '<rect x="8" y="33" width="12" height="18" rx="5" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="44" y="33" width="12" height="18" rx="5" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<circle cx="14" cy="42" r="3" fill="' . $fg . '"/><circle cx="50" cy="42" r="3" fill="' . $fg . '"/>';
        case 'pad':
            return $shadow
                 . '<rect x="6" y="18" width="52" height="30" rx="6" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="6" y="18" width="52" height="30" rx="6" fill="none" stroke="' . $light . '" stroke-width="1.6" stroke-dasharray="3 3"/>'
                 . '<circle cx="48" cy="41" r="4" fill="' . $fg . '" opacity="0.85"/>';
        case 'webcam':
            return $shadow
                 . '<rect x="20" y="42" width="24" height="6" rx="2.5" fill="' . $dark . '"/>'
                 . '<circle cx="32" cy="27" r="16" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<circle cx="32" cy="27" r="9" fill="' . $fg . '"/>'
                 . '<circle cx="29" cy="24" r="3" fill="' . $light . '" opacity="0.9"/>'
                 . '<circle cx="43" cy="17" r="2.4" fill="' . $dark . '"/>';
        case 'speaker':
            return $shadow
                 . '<rect x="20" y="6" width="24" height="46" rx="7" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<circle cx="32" cy="18" r="5" fill="' . $fg . '"/><circle cx="32" cy="18" r="2" fill="' . $light . '"/>'
                 . '<circle cx="32" cy="36" r="9" fill="' . $fg . '"/><circle cx="32" cy="36" r="5.5" fill="' . $dark . '"/><circle cx="32" cy="36" r="2.4" fill="' . $light . '"/>';
        case 'disk':
            return $shadow
                 . '<rect x="8" y="20" width="48" height="24" rx="4" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="8" y="20" width="48" height="7" fill="' . $light . '" opacity="0.7"/>'
                 . '<circle cx="16" cy="32" r="2.6" fill="' . $fg . '"/>'
                 . '<rect x="24" y="30" width="24" height="4" rx="2" fill="' . $fg . '" opacity="0.8"/>'
                 . '<rect x="27" y="44" width="10" height="3" fill="' . $dark . '"/>';
        case 'monitor':
            return $shadow
                 . '<rect x="6" y="9" width="52" height="33" rx="3.5" fill="' . $dark . '"/>'
                 . '<rect x="9.5" y="12.5" width="45" height="26" fill="' . $mid . '"/>'
                 . '<rect x="9.5" y="12.5" width="45" height="10" fill="' . $light . '" opacity="0.55"/>'
                 . '<rect x="27" y="42" width="10" height="7" fill="' . $dark . '"/>'
                 . '<rect x="18" y="49" width="28" height="4" rx="2" fill="' . $dark . '"/>';
        case 'stand':
            return $shadow
                 . '<path d="M10 46l22-30 22 30z" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="6" y="46" width="52" height="4" rx="2" fill="' . $dark . '"/>'
                 . '<path d="M22 46l10-14 10 14z" fill="' . $light . '" opacity="0.7"/>';
        case 'chair':
            return $shadow
                 . '<rect x="16" y="8" width="32" height="20" rx="4" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<line x1="22" y1="12" x2="22" y2="24" stroke="' . $light . '" stroke-width="1.6"/>'
                 . '<line x1="32" y1="12" x2="32" y2="24" stroke="' . $light . '" stroke-width="1.6"/>'
                 . '<line x1="42" y1="12" x2="42" y2="24" stroke="' . $light . '" stroke-width="1.6"/>'
                 . '<rect x="13" y="28" width="38" height="10" rx="3" fill="' . $fg . '"/>'
                 . '<line x1="18" y1="38" x2="16" y2="53" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>'
                 . '<line x1="46" y1="38" x2="48" y2="53" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>';
        case 'lamp':
            return $shadow
                 . '<path d="M22 10h20l-5 16H27z" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<ellipse cx="32" cy="30" rx="13" ry="5" fill="' . $light . '" opacity="0.55"/>'
                 . '<line x1="32" y1="26" x2="32" y2="46" stroke="' . $dark . '" stroke-width="2.6"/>'
                 . '<rect x="18" y="49" width="28" height="5" rx="2.5" fill="' . $fg . '"/>';
        case 'backpack':
            return $shadow
                 . '<path d="M23 19v-5c0-6 4-9 9-9s9 3 9 9v5" fill="none" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>'
                 . '<rect x="12" y="18" width="40" height="34" rx="8" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="20" y="26" width="24" height="14" rx="3" fill="' . $fg . '"/>'
                 . '<line x1="32" y1="26" x2="32" y2="40" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<circle cx="32" cy="47" r="2.6" fill="' . $dark . '"/>';
        case 'charger':
            return $shadow
                 . '<rect x="18" y="8" width="28" height="20" rx="4" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<circle cx="32" cy="18" r="4.5" fill="' . $light . '" opacity="0.8"/>'
                 . '<line x1="25" y1="28" x2="25" y2="33" stroke="' . $dark . '" stroke-width="2.4"/>'
                 . '<line x1="39" y1="28" x2="39" y2="33" stroke="' . $dark . '" stroke-width="2.4"/>'
                 . '<path d="M22 33h20l-3 19h-14z" fill="' . $fg . '"/>';
        case 'cable':
            return $shadow
                 . '<path d="M12 16c10 0 6 30 20 30s10-30 20-30" fill="none" stroke="' . $mid . '" stroke-width="6" stroke-linecap="round"/>'
                 . '<path d="M12 16c10 0 6 30 20 30s10-30 20-30" fill="none" stroke="' . $light . '" stroke-width="2" stroke-linecap="round" opacity="0.7"/>'
                 . '<rect x="6" y="10" width="12" height="11" rx="2.5" fill="' . $fg . '"/>'
                 . '<rect x="46" y="10" width="12" height="11" rx="2.5" fill="' . $fg . '"/>';
        case 'mic':
            return $shadow
                 . '<path d="M14 27a18 18 0 0 0 36 0" fill="none" stroke="' . $dark . '" stroke-width="2.6" stroke-linecap="round"/>'
                 . '<line x1="32" y1="45" x2="32" y2="52" stroke="' . $dark . '" stroke-width="2.6"/>'
                 . '<line x1="23" y1="53" x2="41" y2="53" stroke="' . $dark . '" stroke-width="2.6" stroke-linecap="round"/>'
                 . '<rect x="22" y="6" width="20" height="34" rx="10" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<circle cx="27" cy="16" r="1.3" fill="' . $fg . '"/><circle cx="32" cy="14" r="1.3" fill="' . $fg . '"/><circle cx="37" cy="16" r="1.3" fill="' . $fg . '"/>'
                 . '<circle cx="27" cy="22" r="1.3" fill="' . $fg . '"/><circle cx="32" cy="20" r="1.3" fill="' . $fg . '"/><circle cx="37" cy="22" r="1.3" fill="' . $fg . '"/>'
                 . '<circle cx="27" cy="28" r="1.3" fill="' . $fg . '"/><circle cx="32" cy="26" r="1.3" fill="' . $fg . '"/><circle cx="37" cy="28" r="1.3" fill="' . $fg . '"/>';
        case 'desk':
            return $shadow
                 . '<rect x="6" y="18" width="52" height="6" rx="2" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="10" y="24" width="4" height="24" fill="' . $dark . '"/><rect x="50" y="24" width="4" height="24" fill="' . $dark . '"/>'
                 . '<line x1="17" y1="30" x2="17" y2="40" stroke="' . $fg . '" stroke-width="2"/><line x1="47" y1="30" x2="47" y2="40" stroke="' . $fg . '" stroke-width="2"/>';
        case 'glasses':
            return $shadow
                 . '<circle cx="19" cy="30" r="10" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.6"/>'
                 . '<circle cx="45" cy="30" r="10" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.6"/>'
                 . '<circle cx="19" cy="30" r="4" fill="' . $light . '" opacity="0.8"/><circle cx="45" cy="30" r="4" fill="' . $light . '" opacity="0.8"/>'
                 . '<line x1="29" y1="28" x2="35" y2="28" stroke="' . $dark . '" stroke-width="2"/>';
        case 'battery':
            return $shadow
                 . '<rect x="8" y="22" width="42" height="20" rx="3.5" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<rect x="50" y="28" width="6" height="8" rx="1.6" fill="' . $dark . '"/>'
                 . '<rect x="12" y="26" width="24" height="12" rx="1.6" fill="' . $fg . '"/>';
        default: // bag / generic
            return $shadow
                 . '<path d="M16 20h32l-3 32H19z" fill="' . $mid . '" stroke="' . $dark . '" stroke-width="1.4"/>'
                 . '<path d="M22 20v-5a10 10 0 0 1 20 0v5" fill="none" stroke="' . $dark . '" stroke-width="3"/>'
                 . '<rect x="24" y="27" width="16" height="4" fill="' . $light . '" opacity="0.8"/>';
    }
}

/** Đếm số lần gọi để tạo id gradient/pattern duy nhất trên mỗi trang. */
function product_visual_seq(): int {
    static $n = 0;
    return ++$n;
}

/**
 * Xuất "ảnh" sản phẩm hoàn chỉnh: khung bo góc có nền gradient + hoạ tiết chấm
 * bi trang trí, chứa hình minh hoạ SVG chi tiết riêng cho từng sản phẩm.
 * Dùng để chèn trong .thumb / .product-detail-media.
 */
function product_visual(string $name, int $size = 56): string {
    $meta = product_visual_meta($name);
    $uid = 'pv' . product_visual_seq();
    $iconSize = (int) round($size * 0.82);
    $bgLight = hex_lighten($meta['bg'], 40);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="' . $iconSize . '" height="' . $iconSize . '" style="display:block;">'
         . '<defs>'
         . '<linearGradient id="grad-' . $uid . '" x1="0" y1="0" x2="1" y2="1">'
         . '<stop offset="0" stop-color="' . $bgLight . '"/><stop offset="1" stop-color="' . $meta['bg'] . '"/>'
         . '</linearGradient>'
         . '<pattern id="dots-' . $uid . '" width="8" height="8" patternUnits="userSpaceOnUse">'
         . '<circle cx="1.2" cy="1.2" r="1.1" fill="' . $meta['fg'] . '" opacity="0.10"/>'
         . '</pattern>'
         . '</defs>'
         . '<rect x="0" y="0" width="64" height="64" rx="12" fill="url(#grad-' . $uid . ')"/>'
         . '<rect x="0" y="0" width="64" height="64" rx="12" fill="url(#dots-' . $uid . ')"/>'
         . product_illustration_svg($meta['shape'], $meta['fg'])
         . '</svg>';

    return '<div class="prod-photo" style="width:' . $size . 'px;height:' . $size . 'px;">' . $svg . '</div>';
}

function render_stars(float $rating): string {
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $stars = str_repeat('★', $full);
    if ($half) { $stars .= '½'; }
    return $stars;
}

function stock_label(int $stock): array {
    if ($stock <= 0) {
        return ['out', 'Hết hàng'];
    }
    if ($stock <= 5) {
        return ['low', 'Chỉ còn ' . $stock . ' sản phẩm'];
    }
    return ['in', 'Còn ' . $stock . ' sản phẩm'];
}

/**
 * Cây danh mục (bậc 1 -> bậc 2), dùng để render thanh bên và để whitelist
 * giá trị category/subcategory trước khi đưa vào câu truy vấn.
 */
function category_tree(): array {
    return [
        'phu-kien-may-tinh' => [
            'label' => 'Phụ kiện máy tính',
            'subs' => [
                'ban-phim'  => 'Bàn phím',
                'chuot'     => 'Chuột',
                'lot-chuot' => 'Bàn di chuột',
                'webcam'    => 'Webcam',
            ],
        ],
        'am-thanh' => [
            'label' => 'Âm thanh',
            'subs' => [
                'tai-nghe' => 'Tai nghe',
                'loa'      => 'Loa',
                'micro'    => 'Micro',
            ],
        ],
        'luu-tru-ket-noi' => [
            'label' => 'Lưu trữ & kết nối',
            'subs' => [
                'o-cung'  => 'Ổ cứng',
                'cap-sac' => 'Cáp & phụ kiện kết nối',
            ],
        ],
        'man-hinh-gia-do' => [
            'label' => 'Màn hình & giá đỡ',
            'subs' => [
                'man-hinh' => 'Màn hình',
                'gia-do'   => 'Giá đỡ',
            ],
        ],
        'noi-that-van-phong' => [
            'label' => 'Nội thất văn phòng',
            'subs' => [
                'ghe'     => 'Ghế',
                'den-ban' => 'Đèn bàn',
            ],
        ],
        'phu-kien-di-dong' => [
            'label' => 'Phụ kiện di động & sạc',
            'subs' => [
                'balo' => 'Balo & túi',
                'sac'  => 'Sạc & phụ kiện',
            ],
        ],
    ];
}

/** Danh sách trạng thái đơn hàng hợp lệ, dùng cho cả trang khách và trang admin. */
function order_status_list(): array {
    return [
        'Chờ xác nhận',
        'Đang xử lý',
        'Đang giao',
        'Đã giao',
        'Đã hủy',
        'Yêu cầu hoàn hàng',
        'Đã hoàn hàng',
        'Từ chối hoàn hàng',
    ];
}

/** Khách chỉ được huỷ khi đơn chưa được giao. */
function order_can_cancel(string $status): bool {
    return in_array($status, ['Chờ xác nhận', 'Đang xử lý'], true);
}

/** Khách chỉ được yêu cầu hoàn hàng khi đơn đã giao thành công. */
function order_can_return(string $status): bool {
    return $status === 'Đã giao';
}

/**
 * Bảng phí vận chuyển demo theo khu vực (đơn giản hoá cho đồ án — không gọi
 * API vận chuyển thật). Trả về mảng ['khu vực' => phí].
 */
function shipping_rate_table(): array {
    return [
        'noi-thanh' => ['label' => 'Nội thành (TP lớn)',        'fee' => 20000],
        'ngoai-thanh' => ['label' => 'Ngoại thành / lân cận',    'fee' => 35000],
        'tinh-khac' => ['label' => 'Tỉnh/thành khác',            'fee' => 50000],
    ];
}

function shipping_fee_for(string $zone, float $subtotal): float {
    $table = shipping_rate_table();
    if (!isset($table[$zone])) {
        return 0.0;
    }
    // Miễn phí ship cho đơn từ 1.000.000đ trở lên.
    if ($subtotal >= 1000000) {
        return 0.0;
    }
    return (float)$table[$zone]['fee'];
}

/**
 * Kiểm tra voucher hợp lệ với tổng tiền hàng $subtotal.
 * Trả về ['ok'=>bool, 'message'=>string, 'discount'=>float, 'voucher'=>array|null]
 */
function validate_voucher(mysqli $conn, string $code, float $subtotal): array {
    $code = trim($code);
    if ($code === '') {
        return ['ok' => false, 'message' => 'Vui lòng nhập mã voucher.', 'discount' => 0, 'voucher' => null];
    }
    $stmt = $conn->prepare("SELECT * FROM vouchers WHERE code = ? AND active = 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $voucher = $stmt->get_result()->fetch_assoc();

    if (!$voucher) {
        return ['ok' => false, 'message' => 'Mã voucher không tồn tại hoặc đã bị vô hiệu hoá.', 'discount' => 0, 'voucher' => null];
    }
    if ($voucher['expires_at'] !== null && strtotime($voucher['expires_at']) < strtotime(date('Y-m-d'))) {
        return ['ok' => false, 'message' => 'Mã voucher đã hết hạn.', 'discount' => 0, 'voucher' => null];
    }
    if ($voucher['max_uses'] !== null && (int)$voucher['used_count'] >= (int)$voucher['max_uses']) {
        return ['ok' => false, 'message' => 'Mã voucher đã hết lượt sử dụng.', 'discount' => 0, 'voucher' => null];
    }
    if ($subtotal < (float)$voucher['min_order']) {
        return ['ok' => false, 'message' => 'Đơn hàng cần tối thiểu ' . number_format((float)$voucher['min_order'], 0, ',', '.') . 'đ để dùng mã này.', 'discount' => 0, 'voucher' => null];
    }

    if ($voucher['discount_type'] === 'percent') {
        $discount = $subtotal * ((float)$voucher['discount_value'] / 100);
    } else {
        $discount = (float)$voucher['discount_value'];
    }
    $discount = min($discount, $subtotal);

    return ['ok' => true, 'message' => 'Áp dụng voucher thành công.', 'discount' => $discount, 'voucher' => $voucher];
}

/**
 * Các trạng thái đơn hàng được xem là "đã giải phóng tồn kho" (đơn không còn
 * hiệu lực bán hàng nữa). Dùng để đồng bộ việc cộng/trừ lại `stock` khi trạng
 * thái đơn hàng thay đổi qua lại giữa các nhóm này.
 */
function order_stock_released_statuses(): array {
    return ['Đã hủy', 'Đã hoàn hàng'];
}

/**
 * Đổi trạng thái đơn hàng và tự động đồng bộ tồn kho:
 * - Khi đơn CHUYỂN VÀO "Đã hủy" / "Đã hoàn hàng" (từ trạng thái khác) -> cộng lại
 *   tồn kho các sản phẩm trong đơn (vì đơn không còn hiệu lực).
 * - Khi đơn được khôi phục từ "Đã hủy" / "Đã hoàn hàng" sang trạng thái hoạt động
 *   khác -> trừ lại tồn kho tương ứng (và từ chối nếu không còn đủ hàng).
 * Toàn bộ thao tác nằm trong 1 transaction để tránh lệch dữ liệu.
 *
 * @return array{ok: bool, message: string}
 */
function update_order_status(mysqli $conn, int $orderId, string $newStatus): array {
    if (!in_array($newStatus, order_status_list(), true)) {
        return ['ok' => false, 'message' => 'Trạng thái không hợp lệ.'];
    }

    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) {
        return ['ok' => false, 'message' => 'Không tìm thấy đơn hàng.'];
    }

    $oldStatus = $order['status'];
    if ($oldStatus === $newStatus) {
        return ['ok' => true, 'message' => 'Trạng thái không đổi.'];
    }

    $released = order_stock_released_statuses();
    $wasReleased = in_array($oldStatus, $released, true);
    $willBeReleased = in_array($newStatus, $released, true);

    $conn->begin_transaction();
    try {
        if (!$wasReleased && $willBeReleased) {
            // Đơn bị huỷ / hoàn hàng -> cộng lại tồn kho.
            $items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->bind_param('i', $orderId);
            $items->execute();
            $rows = $items->get_result()->fetch_all(MYSQLI_ASSOC);
            $restore = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            foreach ($rows as $it) {
                $restore->bind_param('ii', $it['quantity'], $it['product_id']);
                $restore->execute();
            }
        } elseif ($wasReleased && !$willBeReleased) {
            // Khôi phục đơn đã huỷ / hoàn hàng về trạng thái hoạt động -> trừ lại
            // tồn kho; từ chối nếu không còn đủ hàng cho bất kỳ sản phẩm nào.
            $items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->bind_param('i', $orderId);
            $items->execute();
            $rows = $items->get_result()->fetch_all(MYSQLI_ASSOC);

            $deduct = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($rows as $it) {
                $deduct->bind_param('iii', $it['quantity'], $it['product_id'], $it['quantity']);
                $deduct->execute();
                if ($deduct->affected_rows < 1) {
                    $conn->rollback();
                    return ['ok' => false, 'message' => 'Không thể khôi phục đơn hàng: không còn đủ tồn kho cho một sản phẩm trong đơn.'];
                }
            }
        }

        $u = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $u->bind_param('si', $newStatus, $orderId);
        $u->execute();
        $conn->commit();
        return ['ok' => true, 'message' => 'Đã cập nhật trạng thái đơn hàng.'];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'message' => 'Có lỗi xảy ra khi cập nhật đơn hàng, vui lòng thử lại.'];
    }
}

/** Người dùng đã mua (đơn đã giao) sản phẩm $productId hay chưa. */
function user_has_purchased(mysqli $conn, int $userId, int $productId): bool {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) c FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Đã giao'"
    );
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['c'] > 0;
}

/** Số lượt thích hiện tại của một nhận xét. */
function review_like_count(mysqli $conn, int $reviewId): int {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM review_likes WHERE review_id = ?");
    $stmt->bind_param('i', $reviewId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['c'];
}

/** Người dùng $userId đã thích nhận xét $reviewId hay chưa. */
function user_has_liked_review(mysqli $conn, int $userId, int $reviewId): bool {
    $stmt = $conn->prepare("SELECT 1 FROM review_likes WHERE review_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $reviewId, $userId);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_assoc();
}

/**
 * Bật/tắt lượt thích của $userId trên nhận xét $reviewId.
 * Trả về true nếu sau thao tác là "đã thích", false nếu là "đã bỏ thích".
 */
function toggle_review_like(mysqli $conn, int $userId, int $reviewId): bool {
    if (user_has_liked_review($conn, $userId, $reviewId)) {
        $stmt = $conn->prepare("DELETE FROM review_likes WHERE review_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $reviewId, $userId);
        $stmt->execute();
        return false;
    }
    $stmt = $conn->prepare("INSERT INTO review_likes (review_id, user_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $reviewId, $userId);
    $stmt->execute();
    return true;
}

/**
 * Vẽ 1 avatar tròn (ảnh nếu user đã có, hoặc chữ cái đầu tên nếu chưa).
 * - $isOwner = true: bọc avatar trong <a> trỏ tới trang Hồ sơ (mở đúng phần
 *   upload ảnh đại diện) và thêm icon bút chì nhỏ, gợi ý "bấm để đổi ảnh".
 * - $isOwner = false: chỉ hiển thị, không click được (không thể đổi avatar
 *   của người khác).
 */
function avatar_html(?string $avatarPath, string $username, bool $isOwner = false, string $size = 'sm'): string {
    $initial = mb_strtoupper(mb_substr($username, 0, 1));
    $inner = $avatarPath
        ? '<img src="' . htmlspecialchars($avatarPath) . '" alt="Ảnh đại diện của ' . htmlspecialchars($username) . '">'
        : '<span class="avatar-initial">' . htmlspecialchars($initial) . '</span>';

    $class = 'avatar-' . $size;
    if ($isOwner) {
        $class .= ' avatar-clickable';
        return '<a href="profile.php#avatar-upload" class="' . $class . '" title="Bấm để đổi ảnh đại diện của bạn">'
            . $inner . '<span class="avatar-edit-badge">✎</span></a>';
    }
    return '<span class="' . $class . '">' . $inner . '</span>';
}

/** Lưu một trả lời mới cho nhận xét $reviewId. */
function add_review_reply(mysqli $conn, int $reviewId, int $userId, string $content): void {
    $stmt = $conn->prepare("INSERT INTO review_replies (review_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $reviewId, $userId, $content);
    $stmt->execute();
}

/** Danh sách trả lời của một nhận xét, kèm username và role người trả lời. */
function get_review_replies(mysqli $conn, int $reviewId): array {
    $stmt = $conn->prepare(
        "SELECT rr.content, rr.created_at, u.id AS user_id, u.username, u.role, u.avatar
         FROM review_replies rr JOIN users u ON rr.user_id = u.id
         WHERE rr.review_id = ? ORDER BY rr.created_at ASC"
    );
    $stmt->bind_param('i', $reviewId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
