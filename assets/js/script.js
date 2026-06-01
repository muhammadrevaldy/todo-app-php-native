// assets/js/script.js

// 1. Fitur Interaksi Konfirmasi Hapus Data
function confirmDelete(event, taskTitle) {
    event.preventDefault();
    const url = event.currentTarget.getAttribute('href');
    
    // Gunakan modal kustom non-alert/confirm bawaan browser untuk estetika (opsional)
    const yakin = window.confirm(`Apakah Anda yakin ingin menghapus tugas "${taskTitle}"?`);
    if (yakin) {
        window.location.href = url;
    }
    return false;
}

// 2. Dark Mode System Preferences & Local Storage
document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('darkModeToggle');
    if (toggleButton) {
        // Ambil preferensi tema dari localStorage jika sudah disimpan sebelumnya
        const savedTheme = localStorage.getItem('theme');
        
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }

        // Event listener saat tombol dark mode diklik
        toggleButton.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            
            // Simpan status tema ke local storage
            if (document.body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }
});
