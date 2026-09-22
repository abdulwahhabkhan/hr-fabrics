import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import 'sweetalert2/themes/bootstrap-5.css';

const ReactSwal = withReactContent(Swal);

const swalButtons = (confirmStyle, cancelStyle) => ({
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
        confirmButton: `btn btn-${confirmStyle} me-2`,
        cancelButton: `btn btn-${cancelStyle}`,
    },
});

/**
 * Global confirmation dialog. Call from anywhere, no local component state needed.
 *
 * @param {object} options
 * @returns {Promise<import('sweetalert2').SweetAlertResult>}
 */
export const confirmSwal = ({
    title = "Are you sure?",
    text = "",
    icon = "warning",
    confirmButtonText = "Proceed",
    cancelButtonText = "Cancel",
    confirmButtonStyle = "warning",
    cancelButtonStyle = "white",
} = {}) =>
    ReactSwal.fire({
        theme: 'bootstrap-5',
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        ...swalButtons(confirmButtonStyle, cancelButtonStyle),
    });

/**
 * Global delete confirmation. When `onConfirm` is given, it runs while the
 * dialog shows a loading state on the confirm button, and the dialog only
 * closes once it resolves.
 *
 * @param {object} options
 * @param {string} [options.text]
 * @param {() => Promise<any>} [options.onConfirm]
 * @returns {Promise<import('sweetalert2').SweetAlertResult>}
 */
export const confirmDelete = ({ text = "You will not be able to recover this resource!", onConfirm } = {}) =>
    ReactSwal.fire({
        theme: 'bootstrap-5',
        title: "Are you sure?",
        text,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        showLoaderOnConfirm: !!onConfirm,
        allowOutsideClick: () => !ReactSwal.isLoading(),
        ...swalButtons("danger", "white"),
        preConfirm: onConfirm
            ? async () => {
                  try {
                      await onConfirm();
                  } catch (error) {
                      ReactSwal.showValidationMessage("Something went wrong, please try again.");
                  }
              }
            : undefined,
    });
