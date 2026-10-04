import mappingComponent from "@/forms/componenets/mapping-component";
import phoneTagsInput from "@/forms/componenets/phone-tags-input";
import smsStats from "@/forms/componenets/sms-stats";

document.addEventListener("alpine:init", () => {
    Alpine.data('mappingComponent', mappingComponent)
    Alpine.data('phoneTagsInput', phoneTagsInput)
    Alpine.data('smsStats', smsStats)
})
