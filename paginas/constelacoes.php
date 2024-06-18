<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esfera Central com Lista de Esferas Conectadas</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            flex-direction: column;
        }
        #animationSVG {
            overflow: visible;
            margin-bottom: 20px;
            border: 1px solid black;
        }
        button {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <button onclick="addItem()">Adicionar Esfera</button>
    <svg id="animationSVG" width="600" height="600">
        <!-- Esfera central -->
        <circle id="centerCircle" cx="300" cy="300" r="20" stroke="black" stroke-width="2" fill="red" />
    </svg>

    <script>
        const svg = document.getElementById('animationSVG');
        const centerCircle = document.getElementById('centerCircle');
        const radius = 150; // Raio da circunferência onde as esferas serão posicionadas
        const angleIncrement = 40; // Ângulo em graus entre esferas consecutivas
        let itemCount = 0;
        let currentAngle = 0;
        const circles = []; // Lista de círculos para referência
        let zoomLevel = 1;

        // Adicionar zoom com scroll do mouse
        svg.addEventListener('wheel', (event) => {
            event.preventDefault();
            if (event.deltaY < 0) {
                zoomLevel *= 1.1;
            } else {
                zoomLevel /= 1.1;
            }
            svg.setAttribute('transform', `scale(${zoomLevel})`);
        });

        function addItem() {
            itemCount++;

            let newX, newY, connectX, connectY;
            const newCircleIndex = (itemCount - 1) % 9;

            if (itemCount <= 9) {
                const angle = currentAngle * (Math.PI / 180); // Convertendo para radianos
                newX = 300 + radius * Math.cos(angle);
                newY = 300 + radius * Math.sin(angle);
                connectX = centerCircle.getAttribute('cx');
                connectY = centerCircle.getAttribute('cy');
                currentAngle += angleIncrement; // Atualiza o ângulo para a próxima esfera
            } else {
                const layer = Math.floor((itemCount - 1) / 9);
                const referenceCircle = circles[newCircleIndex + (layer - 1) * 9];
                connectX = referenceCircle.getAttribute('cx');
                connectY = referenceCircle.getAttribute('cy');
                const angle = (currentAngle + newCircleIndex * angleIncrement) * (Math.PI / 180); // Convertendo para radianos
                newX = parseFloat(connectX) + radius * Math.cos(angle);
                newY = parseFloat(connectY) + radius * Math.sin(angle);
                if (newCircleIndex === 8) currentAngle += angleIncrement; // Atualiza o ângulo após completar uma camada
            }

            const newCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            newCircle.setAttribute('cx', newX);
            newCircle.setAttribute('cy', newY);
            newCircle.setAttribute('r', 20);
            newCircle.setAttribute('stroke', 'black');
            newCircle.setAttribute('stroke-width', 2);
            newCircle.setAttribute('fill', 'blue');
            svg.appendChild(newCircle);

            const newLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            newLine.setAttribute('x1', connectX);
            newLine.setAttribute('y1', connectY);
            newLine.setAttribute('x2', newX);
            newLine.setAttribute('y2', newY);
            newLine.setAttribute('stroke', 'black');
            newLine.setAttribute('stroke-width', 2);
            svg.insertBefore(newLine, newCircle);

            circles.push(newCircle); // Adiciona o novo círculo à lista de círculos
        }
    </script>
</body>
</html>
