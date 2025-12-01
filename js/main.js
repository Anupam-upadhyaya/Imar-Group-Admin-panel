const initializeStorage = () => {
    if (!localStorage.getItem('adminUsers')) {
        localStorage.setItem('adminUsers', JSON.stringify([
            { id: 1, email: 'admin@imargroup.com', password: 'admin123' }
        ]));
    }

    if (!localStorage.getItem('galleries')) 
        localStorage.setItem('galleries', JSON.stringify([]));

    if (!localStorage.getItem('blogs')) 
        localStorage.setItem('blogs', JSON.stringify([]));

    if (!localStorage.getItem('services')) 
        localStorage.setItem('services', JSON.stringify([]));

    if (!localStorage.getItem('contacts')) 
        localStorage.setItem('contacts', JSON.stringify([]));
};


initializeStorage();

const handleLogin = (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorMsg = document.getElementById('errorMessage');

    const users = JSON.parse(localStorage.getItem('adminUsers'));

    const user = users.find(u => u.email === email && u.password === password);

    if (user) {
        localStorage.setItem('currentUser', JSON.stringify(user));
        showAdminPanel();
    } else {
        errorMsg.textContent = 'Invalid email or password';
        errorMsg.style.display = 'block';
    }
};


    // Show Admin Panel
    const showAdminPanel = () => {
        document.getElementById('loginPage').style.display = 'none';
        document.getElementById('adminPanel').classList.add('active');
        const user = JSON.parse(localStorage.getItem('currentUser'));
        document.getElementById('userEmail').textContent = user.email;
        document.getElementById('userAvatar').textContent = user.email.charAt(0).toUpperCase();
        loadAllData();
    };

    // Logout Handler
    const handleLogout = () => {
        localStorage.removeItem('currentUser');
        document.getElementById('adminPanel').classList.remove('active');
        document.getElementById('loginPage').style.display = 'flex';
        document.getElementById('loginForm').reset();
    };

const switchSection = (sectionName, event) => {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(a => a.classList.remove('active'));
    
    document.getElementById(sectionName).classList.add('active');
    event.target.closest('.nav-link').classList.add('active');

    const titles = {
        dashboard: 'Dashboard',
        gallery: 'Gallery Management',
        blogs: 'Blog Management',
        services: 'Services Management',
        contacts: 'Contact Submissions',
        users: 'User Management'
    };

    document.getElementById('pageTitle').textContent = titles[sectionName];
};


    // Gallery Upload Handler
    const previewGalleryImages = () => {
        const files = document.getElementById('galleryFiles').files;
        const preview = document.getElementById('galleryPreview');
        preview.innerHTML = '';

        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `<img src="${e.target.result}" alt="preview">`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    };

    const handleGalleryUpload = (e) => {
        e.preventDefault();
        const category = document.getElementById('galleryCategory').value;
        const files = document.getElementById('galleryFiles').files;
        
        if (!category || files.length === 0) {
            showAlert('galleryAlert', 'Please select category and upload images', 'error');
            return;
        }

        let galleries = JSON.parse(localStorage.getItem('galleries'));
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                galleries.push({
                    id: Date.now(),
                    category: category,
                    image: e.target.result
                });
                localStorage.setItem('galleries', JSON.stringify(galleries));
            };
            reader.readAsDataURL(file);
        });

        showAlert('galleryAlert', 'Gallery images uploaded successfully!', 'success');
        document.getElementById('galleryForm').reset();
        document.getElementById('galleryPreview').innerHTML = '';
        loadGallery();
    };

    // Blog Handler
    const handleBlogSubmit = (e) => {
        e.preventDefault();
        const title = document.getElementById('blogTitle').value;
        const category = document.getElementById('blogCategory').value;
        const author = document.getElementById('blogAuthor').value;
        const excerpt = document.getElementById('blogExcerpt').value;
        const content = document.getElementById('blogContent').value;
        const imageFile = document.getElementById('blogImage').files[0];

        if (!imageFile) {
            showAlert('blogsAlert', 'Please select a featured image', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            let blogs = JSON.parse(localStorage.getItem('blogs'));
            blogs.push({
                id: Date.now(),
                title,
                category,
                author,
                excerpt,
                content,
                image: e.target.result,
                date: new Date().toISOString().split('T')[0]
            });
            localStorage.setItem('blogs', JSON.stringify(blogs));
            showAlert('blogsAlert', 'Blog published successfully!', 'success');
            document.getElementById('blogForm').reset();
            loadBlogs();
        };
        reader.readAsDataURL(imageFile);
    };

    // Service Handler
    const handleServiceSubmit = (e) => {
        e.preventDefault();
        const name = document.getElementById('serviceName').value;
        const description = document.getElementById('serviceDescription').value;

        let services = JSON.parse(localStorage.getItem('services'));
        services.push({
            id: Date.now(),
            name,
            description
        });
        localStorage.setItem('services', JSON.stringify(services));
        showAlert('servicesAlert', 'Service added successfully!', 'success');
        document.getElementById('serviceForm').reset();
        loadServices();
    };

    // User Handler
    const handleUserSubmit = (e) => {
        e.preventDefault();
        const email = document.getElementById('userEmail').value;
        const password = document.getElementById('userPassword').value;

        let users = JSON.parse(localStorage.getItem('adminUsers'));
        if (users.find(u => u.email === email)) {
            showAlert('usersAlert', 'User already exists!', 'error');
            return;
        }

        users.push({
            id: Date.now(),
            email,
            password
        });
        localStorage.setItem('adminUsers', JSON.stringify(users));
        showAlert('usersAlert', 'Admin user added successfully!', 'success');
        document.getElementById('userForm').reset();
        loadUsers();
    };

    // Load All Data
    const loadAllData = () => {
        loadGallery();
        loadBlogs();
        loadServices();
        loadContacts();
        loadUsers();
        updateDashboard();
    };

    // Load Gallery
    const loadGallery = () => {
        const galleries = JSON.parse(localStorage.getItem('galleries'));
        const galleryList = document.getElementById('galleryList');
        galleryList.innerHTML = '';

        galleries.forEach(g => {
            const div = document.createElement('div');
            div.className = 'gallery-item';
            div.innerHTML = `
                <img src="${g.image}" alt="Gallery">
                <button class="gallery-item-delete" onclick="deleteGallery(${g.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            galleryList.appendChild(div);
        });

        document.getElementById('galleryCount').textContent = galleries.length;
    };

    // Delete Gallery
    const deleteGallery = (id) => {
        if (confirm('Are you sure you want to delete this image?')) {
            let galleries = JSON.parse(localStorage.getItem('galleries'));
            galleries = galleries.filter(g => g.id !== id);
            localStorage.setItem('galleries', JSON.stringify(galleries));
            loadGallery();
        }
    };

    // Load Blogs
    const loadBlogs = () => {
        const blogs = JSON.parse(localStorage.getItem('blogs'));
        const tbody = document.getElementById('blogsTableBody');
        tbody.innerHTML = '';

        blogs.forEach(blog => {
            tbody.innerHTML += `
                <tr>
                    <td>${blog.title}</td>
                    <td>${blog.category}</td>
                    <td>${blog.author}</td>
                    <td>${blog.date}</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteBlog(${blog.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        document.getElementById('blogCount').textContent = blogs.length;
    };

    // Delete Blog
    const deleteBlog = (id) => {
        if (confirm('Are you sure you want to delete this blog?')) {
            let blogs = JSON.parse(localStorage.getItem('blogs'));
            blogs = blogs.filter(b => b.id !== id);
            localStorage.setItem('blogs', JSON.stringify(blogs));
            loadBlogs();
        }
    };

    // Load Services
    const loadServices = () => {
        const services = JSON.parse(localStorage.getItem('services'));
        tbody.innerHTML = '';

        services.forEach(service => {
            tbody.innerHTML += `
                <tr>
                    <td>${service.name}</td>
                    <td>${service.description.substring(0, 50)}...</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteService(${service.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        document.getElementById('serviceCount').textContent = services.length;
    };

    // Delete Service
    const deleteService = (id) => {
        if (confirm('Are you sure you want to delete this service?')) {
            let services = JSON.parse(localStorage.getItem('services'));
            services = services.filter(s => s.id !== id);
            localStorage.setItem('services', JSON.stringify(services));
            loadServices();
        }
    };

    // Load Contacts
    const loadContacts = () => {
        const contacts = JSON.parse(localStorage.getItem('contacts'));
        const tbody = document.getElementById('contactsTableBody');
        tbody.innerHTML = '';

        contacts.forEach(contact => {
            tbody.innerHTML += `
                <tr>
                    <td>${contact.firstName} ${contact.lastName}</td>
                    <td>${contact.email}</td>
                    <td>${contact.phone}</td>
                    <td>${contact.message.substring(0, 50)}...</td>
                    <td>${contact.date}</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteContact(${contact.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        document.getElementById('contactCount').textContent = contacts.length;
    };

    // Delete Contact
    const deleteContact = (id) => {
        if (confirm('Are you sure you want to delete this contact?')) {
            let contacts = JSON.parse(localStorage.getItem('contacts'));
            contacts = contacts.filter(c => c.id !== id);
            localStorage.setItem('contacts', JSON.stringify(contacts));
            loadContacts();
        }
    };

    // Load Users
    const loadUsers = () => {
        const users = JSON.parse(localStorage.getItem('adminUsers'));
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '';

        users.forEach(user => {
            tbody.innerHTML += `
                <tr>
                    <td>${user.email}</td>
                    <td>${new Date().toISOString().split('T')[0]}</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteUser(${user.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });
    };

    // Delete User
    const deleteUser = (id) => {
        if (confirm('Are you sure you want to delete this user?')) {
            let users = JSON.parse(localStorage.getItem('adminUsers'));
            if (users.length === 1) {
                alert('Cannot delete the last admin user!');
                return;
            }
            users = users.filter(u => u.id !== id);
            localStorage.setItem('adminUsers', JSON.stringify(users));
            loadUsers();
        }
    };

    // Update Dashboard
    const updateDashboard = () => {
        document.getElementById('galleryCount').textContent = JSON.parse(localStorage.getItem('galleries')).length;
        document.getElementById('blogCount').textContent = JSON.parse(localStorage.getItem('blogs')).length;
        document.getElementById('serviceCount').textContent = JSON.parse(localStorage.getItem('services')).length;
        document.getElementById('contactCount').textContent = JSON.parse(localStorage.getItem('contacts')).length;
    };

    // Show Alert
    const showAlert = (elementId, message, type) => {
        const alert = document.getElementById(elementId);
        alert.textContent = message;
        alert.className = `alert show alert-${type}`;
        setTimeout(() => alert.classList.remove('show'), 3000);
    };

    // Check if user is logged in
window.addEventListener('load', () => {
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        showAdminPanel();
    }
});