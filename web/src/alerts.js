import Swal from "sweetalert2";

export function showAlert(title, text, icon = "info") {
  return Swal.fire({ title, text, icon, confirmButtonText: "Mengerti" });
}

export async function confirmAction(title, text, confirmText = "Ya, lanjutkan") {
  const result = await Swal.fire({
    title,
    text,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: "Batal",
    reverseButtons: true,
  });
  return result.isConfirmed;
}

export async function promptText(title, text, confirmText = "Simpan") {
  const result = await Swal.fire({
    title,
    text,
    input: "textarea",
    inputPlaceholder: "Tulis catatan...",
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: "Batal",
    reverseButtons: true,
  });
  return result.isConfirmed ? result.value : null;
}
