<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Cart</title>

<style>
body{
    font-family: Arial, sans-serif;
    background:#bdc9e0;
    padding:30px;
}

h1{
    margin-bottom:20px;
}

.cart-container{
    background:white;
    padding:20px;
    border-radius:15px;
    max-width:800px;
    margin:auto;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

.cart-item{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 0;
    border-bottom:1px solid #ddd;
}

.cart-info{
    flex:1;
}

.cart-item button{
    background:#315f99;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}

.cart-item button:hover{
    background:#214a7a;
}

.total{
    margin-top:20px;
    font-size:22px;
    font-weight:bold;
    text-align:right;
}

.empty{
    text-align:center;
    padding:40px;
    color:#555;
}
.back-btn{
    background:#417cd0;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
}
.checkout-btn{
    background:#28a745;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
}

.icon-btn{
    display:inline-block;
    margin-top:20px;
    background:#417cd0;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
}

.icon-btn:hover{
    background:#315f99;
}

    </style>
</head>

<body>

<div class="cart-container">

    <h1>Your Cart</h1>
    <div id="cart-items"></div>
    <div class="total" id="cart-total">
        Total: R0.00
    </div>

    <button class="icon-btn" id="back-btn" onclick="window.location.href='Thrift.php'" type="button">Continue Shopping</button><br><br>
    </a>
    <button class="icon-btn" id="checkout" onclick="window.location.href='address.php'" type="button">Checkout</button>
</div>

<script>
function loadCart(){

    const cart = JSON.parse(localStorage.getItem('thrift-cart') || '[]');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    cartItems.innerHTML = '';

    let total = 0;
    if(cart.length === 0){
        cartItems.innerHTML = `
            <div class="empty">
                Your cart is empty.
            </div>
        `;
        cartTotal.textContent = 'Total: R0.00';
        return;
    }

    cart.forEach((item,index)=>{
        total += item.price * item.quantity;
        cartItems.innerHTML += `
            <div class="cart-item">
                <div class="cart-info">
                    <h3>${item.name}</h3>
                    <p>
                        Quantity: ${item.quantity}
                    </p>

                    <p>
                        Price: R${item.price.toFixed(2)}
                    </p>
                </div>
                <button onclick="removeItem(${index})">
                    Remove
                </button>
            </div>
        `;
    });
    cartTotal.textContent = `Total: R${total.toFixed(2)}`;
}

function removeItem(index){
    const cart = JSON.parse(localStorage.getItem('thrift-cart') || '[]');
    cart.splice(index,1);
    localStorage.setItem('thrift-cart', JSON.stringify(cart));
    loadCart();
}
loadCart();

</script>

</body>
</html>