@extends('app')

@section('content')
    <div style="max-width:450px; margin:60px auto; font-family:sans-serif; background:#faf9f0; padding:24px; border-radius:12px;">
        <h2 style="color:#2d3a1f;">My Profile</h2>
        <div id="messageBox" style="margin-bottom:15px;"></div>

        <div id="profileView">
            <img id="avatarPreview" src="" style="width:100px; height:100px; border-radius:50%; object-fit:cover; display:none; margin-bottom:15px;">

            <label>Name:</label><br>
            <input type="text" id="profileName" style="width:100%; margin-bottom:10px; padding:6px;"><br>

            <label>Age:</label><br>
            <input type="number" id="profileAge" min="1" max="120" style="width:100%; margin-bottom:10px; padding:6px;"><br>

            <label>Profile Photo:</label><br>
            <input type="file" id="profileAvatar" accept="image/*" style="width:100%; margin-bottom:15px;"><br>

            <button id="saveProfileBtn" style="background:#2d3a1f; color:#faf9f0; border:none; padding:10px 16px; border-radius:8px; cursor:pointer;">
                Save Profile
            </button>
        </div>
    </div>

    <script>
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var messageBox = document.getElementById('messageBox');

        function showMessage(text, isError) {
            messageBox.textContent = text;
            messageBox.style.color = isError ? 'red' : 'green';
        }

        // Load current profile data
        fetch('/api/profile', {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showMessage('You must be logged in to view your profile. Go to /auth to login.', true);
                return;
            }

            document.getElementById('profileName').value = data.name || '';
            document.getElementById('profileAge').value = data.age || '';

            if (data.avatar && data.avatar !== 'default.jpg') {
                var avatarImg = document.getElementById('avatarPreview');
                avatarImg.src = '/storage/' + data.avatar;
                avatarImg.style.display = 'block';
            }
        })
        .catch(error => showMessage('Failed to load profile: ' + error, true));

        document.getElementById('saveProfileBtn').addEventListener('click', function () {
            var name = document.getElementById('profileName').value;
            var age = document.getElementById('profileAge').value;
            var avatarFile = document.getElementById('profileAvatar').files[0];

            var formData = new FormData();
            formData.append('name', name);
            formData.append('age', age);
            if (avatarFile) {
                formData.append('avatar', avatarFile);
            }

            fetch('/api/profile/update', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showMessage(data.error, true);
                    return;
                }
                showMessage('Profile saved!', false);
                if (data.avatar) {
                    var avatarImg = document.getElementById('avatarPreview');
                    avatarImg.src = '/storage/' + data.avatar + '?t=' + Date.now();
                    avatarImg.style.display = 'block';
                }
            })
            .catch(error => showMessage('Save failed: ' + error, true));
        });
    </script>
@endsection