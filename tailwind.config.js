/** @type {import('tailwindcss').Config} */
// Bladeなどで使用されているTailwindクラスを読み込む
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
