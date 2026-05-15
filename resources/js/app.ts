import mappingComponent from "@/forms/componenets/mapping-component";
import recipientUpload from "@/forms/componenets/recipient-upload";
import smsStats from "@/forms/componenets/sms-stats";

document.addEventListener("alpine:init", () => {
    Alpine.data('mappingComponent', mappingComponent)
    Alpine.data('recipientUpload', recipientUpload)
    Alpine.data('smsStats', smsStats)
})
