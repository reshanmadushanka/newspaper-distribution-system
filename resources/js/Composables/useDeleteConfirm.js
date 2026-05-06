import { ref } from 'vue'
import Swal from 'sweetalert2'

/**
 * Composable for handling delete confirmations with SweetAlert2
 * @param {string} entityName - Human readable entity name (e.g., 'user', 'role', 'permission')
 * @returns {Function} confirmDelete - async function that returns true if confirmed, false otherwise
 */
export function useDeleteConfirm(message) {
    const deleting = ref(false)

    const confirmDelete = async (deleteCallback) => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--color-destructive)',
            cancelButtonColor: 'var(--color-muted-foreground)',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl',
                cancelButton: 'rounded-xl'
            }
        })

        if (result.isConfirmed) {
            deleting.value = true
            try {
                await deleteCallback()
            } catch (error) {
                throw error
            } finally {
                deleting.value = false
            }
            return true
        }
        return false
    }

    return {
        deleting,
        confirmDelete
    }
}

