window.onload = () => {
    document.querySelectorAll("[data-srcset-full]").forEach(item => {
        item.setAttribute("srcset", item.dataset.srcsetFull);
    });
};

