<?php
if (!function_exists('render_profile_avatar')) {
    /**
     * Render the user avatar circle with the profile photo if available.
     *
     * @param array $options {
     *     size class(es) to apply to the container (default: 'w-10 h-10').
     *     classes additional Tailwind classes to apply (default: 'bg-maroon text-white font-semibold').
     *     initials fallback initials string (defaults to first letters of user_name or user).
     * }
     */
    function render_profile_avatar(array $options = []) {
        $sizeClasses = $options['size'] ?? 'w-10 h-10';
        $extraClasses = $options['classes'] ?? 'bg-maroon text-white font-semibold';
        $username = $_SESSION['user_name'] ?? 'User';
        $initials = $options['initials'] ?? strtoupper(substr($username, 0, 1));
        $profilePhoto = $_SESSION['profile_photo'] ?? '';

        $styleAttr = '';
        if ($profilePhoto) {
            $safePath = htmlspecialchars($profilePhoto, ENT_QUOTES);
            $styleAttr = "style=\"background-image:url('../{$safePath}'); background-size:cover; background-position:center;\"";
        }

        echo "<div class=\"{$sizeClasses} {$extraClasses} rounded-full flex items-center justify-center overflow-hidden\" {$styleAttr}>";
        if (!$profilePhoto) {
            echo htmlspecialchars($initials, ENT_QUOTES);
        }
        echo "</div>";
    }
}

