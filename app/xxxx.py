from PIL import Image

# Open your original image
img = Image.open("static/favicon.png").convert("RGBA")

# Get pixel data
datas = img.getdata()
newData = []

for item in datas:
    # If pixel is not transparent
    if item[3] > 0:
        # Replace with white but keep alpha
        newData.append((255, 255, 255, item[3]))
    else:
        # Keep transparent background
        newData.append(item)

# Apply changes
img.putdata(newData)

# Save into your static folder
img.save("static/favicon_white.png", "PNG")
