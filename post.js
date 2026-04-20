const postForm = document.getElementById('postForm'); // Формата
const postsContainer = document.getElementById('postsContainer'); // Контейнерот за постовите

postForm.addEventListener('submit', function (event) {
    event.preventDefault(); // Спречи ја формата да го освежи страницата

    const formData = new FormData(postForm);

    fetch('post.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const post = data.post;

                // Креирај нов HTML елемент за постот
                const postElement = document.createElement('div');
                postElement.classList.add('post');
                postElement.innerHTML = `
                    <h3>${post.title}</h3>
                    <p>${post.content}</p>
                    <p><strong>Category:</strong> ${post.category}</p>
                    <p><strong>Location:</strong> ${post.location}</p>
                    <p><strong>Created At:</strong> ${post.created_at}</p>
                    ${post.image_path ? post.image_path.split(',').map(path => `<img src="${path}" alt="Image" style="max-width: 200px;">`).join('') : ''}
                `;

                // Додај го новиот пост на врвот на контейнерот
                postsContainer.prepend(postElement);

                // Исчисти ја формата
                postForm.reset();
            } else {
                console.error('Error:', data.error);
                alert('Не успеавме да го зачуваме постот.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Настана грешка при додавањето на постот.');
        });
});
