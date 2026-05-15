import mappingComponent from "@/forms/componenets/mapping-component";
import recipientUpload from "@/forms/componenets/recipient-upload";

document.addEventListener("alpine:init", () => {
    Alpine.data('mappingComponent', mappingComponent)
    Alpine.data('recipientUpload', recipientUpload)
})
