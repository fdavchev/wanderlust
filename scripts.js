function likePost(postId) {
    fetch('like_post.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Liked successfully!');
        } else {
            alert('Error liking post.');
        }
    });
}


// Коментар на пост
function commentPost(postId) {
    // Овде можете да го имплементирате модалното прозорче за коментари
    alert(`Open comment section for post ID: ${postId}`);
}


// Додавање на пост во буукмарк
function bookmarkPost(postId) {
    fetch('bookmark_post.php', {
        method: 'POST',
        body: JSON.stringify({ post_id: postId }),
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bookmarked successfully!');
            // Повикување функција за прикажување на новите букмаркани постови без да се прави редирекција
            displayBookmarkedPosts(data.posts);
        } else {
            alert('Error bookmarking post.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error bookmarking post.');
    });
}


function displayBookmarkedPosts(posts) {
    let postsContainer = document.querySelector('.bookmarked-posts');
    postsContainer.innerHTML = ''; // Испразни ја старата содржина

    if (posts.length > 0) {
        posts.forEach(post => {
            let postElement = document.createElement('div');
            postElement.classList.add('post');
            postElement.innerHTML = `
                <h3>${post.title}</h3>
                <img src="uploads/${post.image_path}" alt="Image for ${post.title}" style="max-width: 100%; height: auto;">
                <p>${post.content}</p>
                <p><strong>Location:</strong> ${post.location}</p>
                <p><strong>Category:</strong> ${post.category_name}</p>
            `;
            postsContainer.appendChild(postElement);
        });
    } else {
        postsContainer.innerHTML = '<p>No bookmarked posts found.</p>';
    }
}



