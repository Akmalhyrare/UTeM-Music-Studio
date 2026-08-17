<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.settings-tab');
    const tabContents = document.querySelectorAll('.settings-tab-content');

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.tab;

            tabButtons.forEach((btn) => btn.classList.remove('active'));
            tabContents.forEach((content) => content.style.display = 'none');

            button.classList.add('active');
            document.getElementById('tab-' + target).style.display = 'block';
        });
    });
});
</script>
