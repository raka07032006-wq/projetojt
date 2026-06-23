// Main interactive JS helper for Audit 5R Web App

document.addEventListener('DOMContentLoaded', () => {
    // Select image thumbnails and create a modal preview dynamically
    const images = document.querySelectorAll('.img-thumbnail, .comparison-img');
    if (images.length > 0) {
        // Create modal element if not exists
        let modal = document.getElementById('imagePreviewModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'imagePreviewModal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 800px; text-align: center;">
                    <div class="modal-header">
                        <h3 class="modal-title">Pratinjau Foto</h3>
                        <span class="modal-close">&times;</span>
                    </div>
                    <img id="modalPreviewImg" src="" alt="Preview" style="max-width: 100%; max-height: 70vh; border-radius: var(--radius-sm); border: 1px solid var(--border-color); object-fit: contain;">
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close event
            modal.querySelector('.modal-close').addEventListener('click', () => {
                modal.classList.remove('active');
            });
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
        
        // Add click listener via delegation to prevent navigation and open preview modal
        document.addEventListener('click', (e) => {
            const img = e.target.closest('.img-thumbnail, .comparison-img');
            const link = e.target.closest('a[href*="view_image.php"]');
            
            if (img || link) {
                e.preventDefault();
                e.stopPropagation();
                
                const previewImg = document.getElementById('modalPreviewImg');
                let src = '';
                
                if (link) {
                    src = link.href;
                } else if (img) {
                    const parentLink = img.closest('a');
                    src = parentLink ? parentLink.href : img.src;
                }
                
                if (previewImg && src) {
                    previewImg.src = src;
                    modal.classList.add('active');
                }
            }
        });
        
        // Ensure cursor styling is applied on load
        images.forEach(img => {
            img.style.cursor = 'pointer';
        });
    }

    // Confirm deletion
    const deleteButtons = document.querySelectorAll('.action-link.delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });

    // Notification Dropdown Toggle (Universal trigger handling)
    const notifWrappers = document.querySelectorAll('.notification-wrapper');
    notifWrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector('.notification-trigger');
        const dropdown = wrapper.querySelector('.notification-dropdown');
        
        if (trigger && dropdown) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('active');
                
                // Close other dropdowns if open
                notifWrappers.forEach(otherWrapper => {
                    if (otherWrapper !== wrapper) {
                        const otherDropdown = otherWrapper.querySelector('.notification-dropdown');
                        if (otherDropdown) otherDropdown.classList.remove('active');
                    }
                });
            });
        }
    });
    
    document.addEventListener('click', (e) => {
        notifWrappers.forEach(wrapper => {
            const dropdown = wrapper.querySelector('.notification-dropdown');
            const trigger = wrapper.querySelector('.notification-trigger');
            if (dropdown && !wrapper.contains(e.target) && e.target !== trigger) {
                dropdown.classList.remove('active');
            }
        });
    });

    // AJAX Action to Mark All Read
    const markAllReadBtns = document.querySelectorAll('.mark-all-read-btn');
    markAllReadBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            let basePath = '';
            const logoutLink = document.querySelector('.sidebar-logout');
            if (logoutLink) {
                const href = logoutLink.getAttribute('href');
                if (href && href.startsWith('../')) {
                    basePath = '../';
                }
            }
            
            fetch(basePath + 'notifications_action.php?action=mark_all_read')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-badge, #sidebarNotifBadge').forEach(b => b.remove());
                        
                        const unreadItems = document.querySelectorAll('.notif-item.unread, .notif-list-card.unread');
                        unreadItems.forEach(item => {
                            item.classList.remove('unread');
                            item.classList.add('read');
                        });
                        
                        document.querySelectorAll('.mark-all-read-btn').forEach(b => b.remove());
                    }
                })
                .catch(err => console.error('Error marking all as read:', err));
        });
    });

    // AJAX Action to Mark Single Notification as Read on Click
    const notifItems = document.querySelectorAll('.notif-item.unread');
    notifItems.forEach(item => {
        item.addEventListener('click', (e) => {
            const notifId = item.getAttribute('data-id');
            let basePath = '';
            const logoutLink = document.querySelector('.sidebar-logout');
            if (logoutLink) {
                const href = logoutLink.getAttribute('href');
                if (href && href.startsWith('../')) {
                    basePath = '../';
                }
            }
            
            fetch(basePath + 'notifications_action.php?action=read&id=' + notifId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        
                        const badges = document.querySelectorAll('.notification-badge, #sidebarNotifBadge');
                        badges.forEach(badge => {
                            let count = parseInt(badge.textContent, 10);
                            count--;
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                                document.querySelectorAll('.mark-all-read-btn').forEach(b => b.remove());
                            }
                        });
                    }
                })
                .catch(err => console.error('Error marking single as read:', err));
        });
    });

    // Sidebar Collapsible and Mobile Drawer Toggling
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.add('active');
            if (sidebarBackdrop) sidebarBackdrop.classList.add('active');
        });
    }

    // Synchronize UI icon state on load
    if (sidebarToggle) {
        const icon = sidebarToggle.querySelector('.material-symbols-rounded');
        if (icon) {
            const isCollapsed = document.body.classList.contains('collapsed');
            icon.textContent = isCollapsed ? 'chevron_right' : 'chevron_left';
        }
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.innerWidth <= 1024) {
                // Mobile View: Toggle active drawer overlay
                sidebar.classList.toggle('active');
                if (sidebarBackdrop) sidebarBackdrop.classList.toggle('active');
            } else {
                // Desktop View: Toggle collapsed sidebar width
                const isCollapsed = document.body.classList.toggle('collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
                
                // Update icon dynamically
                const icon = sidebarToggle.querySelector('.material-symbols-rounded');
                if (icon) {
                    icon.textContent = isCollapsed ? 'chevron_right' : 'chevron_left';
                }
            }
        });
    }

    // Close mobile drawer when clicking the backdrop
    if (sidebarBackdrop && sidebar) {
        sidebarBackdrop.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarBackdrop.classList.remove('active');
        });
    }

    // Close mobile drawer when clicking outside of the sidebar
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 1024 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('active');
                if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
            }
        }
    });

    // Camera watermark timestamp handler
    document.addEventListener('change', (e) => {
        if (e.target && e.target.classList.contains('file-input-group') && e.target.dataset.type === 'camera') {
            const input = e.target;
            
            // Check if already processed
            if (input.dataset.processed === 'true') {
                input.removeAttribute('data-processed');
                return;
            }
            
            const file = input.files ? input.files[0] : null;
            if (!file) return;
            
            // Find parent form and disable submit buttons
            const form = input.closest('form');
            const submitBtns = form ? form.querySelectorAll('button[type="submit"]') : [];
            const container = input.closest('.custom-file-upload');
            const subtext = container ? container.querySelector('.custom-file-subtext') : null;
            
            // Set indicator and disable buttons
            submitBtns.forEach(btn => {
                btn.disabled = true;
                if (!btn.dataset.originalText) {
                    btn.dataset.originalText = btn.innerHTML;
                }
                btn.textContent = 'Memproses Foto...';
            });
            
            if (subtext) {
                subtext.textContent = 'Menambahkan tanggal...';
            }
            
            try {
                const objectUrl = URL.createObjectURL(file);
                const img = new Image();
                img.onload = function() {
                    try {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        
                        let width = img.naturalWidth || img.width;
                        let height = img.naturalHeight || img.height;
                        
                        // Limit size to avoid browser canvas memory limits on mobile
                        const maxDim = 1200;
                        if (width > maxDim || height > maxDim) {
                            const ratio = width / height;
                            if (width > height) {
                                width = maxDim;
                                height = Math.round(maxDim / ratio);
                            } else {
                                height = maxDim;
                                width = Math.round(maxDim * ratio);
                            }
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        
                        // Draw image resized
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        // Clean memory immediately
                        URL.revokeObjectURL(objectUrl);
                        
                        // Dynamic font size (about 3.5% of canvas width)
                        const fontSize = Math.max(18, Math.round(width * 0.035));
                        
                        // Date/Time info
                        const now = new Date();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const timeText = `${hours}:${minutes}`;
                        
                        const day = String(now.getDate()).padStart(2, '0');
                        const months = [
                            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                        ];
                        const monthName = months[now.getMonth()];
                        const year = now.getFullYear();
                        const dateText = `${day} ${monthName} ${year}`;
                        
                        // Setup canvas text styles
                        ctx.font = `bold ${fontSize}px sans-serif`;
                        ctx.textAlign = 'right';
                        ctx.textBaseline = 'bottom';
                        
                        // Orange digital stamp look with a black border
                        ctx.fillStyle = '#ff8800';
                        ctx.strokeStyle = '#000000';
                        ctx.lineWidth = Math.max(2, Math.round(fontSize * 0.08));
                        
                        // Coordinates (at bottom-right corner)
                        const paddingX = Math.round(width * 0.04);
                        const paddingY = Math.round(height * 0.04);
                        
                        const x = width - paddingX;
                        const y2 = height - paddingY;
                        const y1 = y2 - Math.round(fontSize * 1.25);
                        
                        // Draw Time
                        ctx.strokeText(timeText, x, y1);
                        ctx.fillText(timeText, x, y1);
                        
                        // Draw Date
                        ctx.strokeText(dateText, x, y2);
                        ctx.fillText(dateText, x, y2);
                        
                        // Output to Blob
                        canvas.toBlob((blob) => {
                            try {
                                if (blob) {
                                    const watermarkedFile = new File([blob], file.name, {
                                        type: 'image/jpeg',
                                        lastModified: Date.now()
                                    });
                                    
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(watermarkedFile);
                                    input.files = dataTransfer.files;
                                    
                                    input.dataset.processed = 'true';
                                }
                                
                                // Restore form state
                                restoreSubmitState(submitBtns);
                                
                                // Trigger change event to refresh custom styled subtexts
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                            } catch (e) {
                                alert("Gagal memperbarui file input: " + e.message);
                                restoreSubmitState(submitBtns);
                            }
                        }, 'image/jpeg', 0.9);
                        
                    } catch (err) {
                        alert("Error pemrosesan gambar: " + err.message);
                        restoreSubmitState(submitBtns);
                    }
                };
                
                img.onerror = function() {
                    URL.revokeObjectURL(objectUrl);
                    alert("Gagal memuat gambar untuk stempel waktu.");
                    restoreSubmitState(submitBtns);
                };
                
                img.src = objectUrl;
                
            } catch (err) {
                alert("Gagal membaca file kamera: " + err.message);
                restoreSubmitState(submitBtns);
            }
        }
    });

    function restoreSubmitState(submitBtns) {
        submitBtns.forEach(btn => {
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
                btn.removeAttribute('data-original-text');
            }
        });
    }
});

