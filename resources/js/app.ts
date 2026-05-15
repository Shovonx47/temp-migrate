import recipientUpload from "@/forms/componenets/recipient-upload";

document.addEventListener("alpine:init", () => {
    Alpine.data('recipientUpload', recipientUpload)
})
