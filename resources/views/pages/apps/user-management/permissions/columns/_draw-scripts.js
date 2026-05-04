// Add click event listener to delete buttons
document.querySelectorAll('[data-kt-action="delete_row"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        window.showDeleteConfirmation({
            title: 'Delete Permission?',
            text: 'This action is permanent and cannot be undone.',
        }).then((confirmed) => {
            if (confirmed) {
                Livewire.dispatch('delete_permission', [this.getAttribute('data-permission-id')]);
            }
        });
    });
});
