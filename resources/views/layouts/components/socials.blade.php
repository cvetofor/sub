<div class="fixed bottom-5 right-5 z-[1000] flex flex-col gap-2.5
            max-[1199px]:bottom-[100px] max-[1199px]:right-[15px] max-[1199px]:gap-2
            max-[767px]:bottom-[90px] max-[767px]:right-[10px] max-[767px]:gap-1.5">
    
    <!-- Telegram -->
    <a href="{{ $telegram }}" target="_blank" 
       class="social-icon w-[50px] h-[50px] rounded-full flex items-center justify-center cursor-pointer 
              bg-gradient-to-br from-[#289ed9] to-[#2ea5dc] shadow-md hover:-translate-y-[3px] hover:shadow-lg
              transition-all duration-300 ease-in-out 
              max-[1199px]:w-[44px] max-[1199px]:h-[44px] 
              max-[767px]:w-[40px] max-[767px]:h-[40px]">
        <img src="https://upload.wikimedia.org/wikipedia/commons/8/82/Telegram_logo.svg" 
             alt="Telegram" 
             class="w-[30px] h-[30px] max-[1199px]:w-[24px] max-[1199px]:h-[24px] max-[767px]:w-[22px] max-[767px]:h-[22px]">
    </a>

    <!-- VK -->
    <a href="{{ $vk }}" target="_blank" 
       class="social-icon w-[50px] h-[50px] rounded-full flex items-center justify-center cursor-pointer 
              bg-[#4a76a8] shadow-md hover:-translate-y-[3px] hover:shadow-lg
              transition-all duration-300 ease-in-out 
              max-[1199px]:w-[44px] max-[1199px]:h-[44px] 
              max-[767px]:w-[40px] max-[767px]:h-[40px]">
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/21/VK.com-logo.svg" 
             alt="VK" 
             class="w-[30px] h-[30px] max-[1199px]:w-[24px] max-[1199px]:h-[24px] max-[767px]:w-[22px] max-[767px]:h-[22px]">
    </a>

    <!-- WhatsApp -->
    <a href="{{ $whatsapp }}" target="_blank" 
       class="social-icon w-[50px] h-[50px] rounded-full flex items-center justify-center cursor-pointer 
              bg-[#25D366] shadow-md hover:-translate-y-[3px] hover:shadow-lg
              transition-all duration-300 ease-in-out 
              max-[1199px]:w-[44px] max-[1199px]:h-[44px] 
              max-[767px]:w-[40px] max-[767px]:h-[40px]">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" 
             alt="WhatsApp" 
             class="w-[30px] h-[30px] max-[1199px]:w-[24px] max-[1199px]:h-[24px] max-[767px]:w-[22px] max-[767px]:h-[22px]">
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const socialIcons = document.querySelectorAll('.social-icon');
        
        socialIcons.forEach((icon, index) => {
            setTimeout(() => {
                icon.style.opacity = '1';
                icon.style.transform = 'translateY(0)';
            }, index * 100);
            
            icon.style.opacity = '0';
            icon.style.transform = 'translateY(20px)';
            icon.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        });
    });
</script>
