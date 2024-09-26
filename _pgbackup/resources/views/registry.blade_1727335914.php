<!DOCTYPE html> 
<html lang="en"> 
    <head> 
        <meta charset="UTF-8"> 
        <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <title>Registry - Generate Token</title>         
        <style>body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; } /* Profile icon styles */.profile { position: absolute; top: 10px; right: 10px; } .profile-icon { width: 40px; height: 40px; border-radius: 50%; background-color: #6c757d; display: inline-block; cursor: pointer; color: white; font-size: 20px; text-align: center; line-height: 40px; user-select: none; } .dropdown { display: none; position: absolute; top: 50px; right: 0; background-color: white; min-width: 150px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2); z-index: 1; border-radius: 5px; padding: 10px; } .dropdown ul { list-style: none; margin: 0; padding: 0; } .dropdown li { margin-bottom: 10px; } .dropdown a { color: black; text-decoration: none; } .dropdown a:hover { background-color: #f1f1f1; } .profile:hover .dropdown { display: block; } h1, h2 { text-align: center; } form { margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; } label, input, button { display: block; width: 100%; margin-bottom: 10px; } input, button { padding: 10px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; } button { background-color: #28a745; color: white; cursor: pointer; } button:hover { background-color: #218838; } table { margin: 20px auto; border-collapse: collapse; width: 80%; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); } th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; } th { background-color: #f4f4f9; } td { font-size: 1rem; } tr:nth-child(even) { background-color: #f9f9f9; } p { text-align: center; } p.success { color: green; font-weight: bold; }</style>         
        <link href="../../css/theme.css" rel="stylesheet" type="text/css">
    </head>     
    <body> 
        <!-- Profile Icon -->         
        <div class="profile">
            <div class="profile-icon">A</div> <!-- Placeholder for profile icon -->
            <div class="dropdown">
                <ul>
                    <li><a href="#">Profile</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>       
        <h1>Generate Charging Token</h1> 
        @if(session('success'))
        <p class="success">{{ session('success') }}</p> 
        @endif
        <form method="POST" action="{{ route('generate-token') }}"> 
            @csrf
            <label for="expiry">Token Expiry (in minutes):</label>             
            <input type="number" name="expiry" id="expiry" value="720" required> 
            <label for="duration">Timer Duration (in minutes):</label>             
            <input type="number" name="duration" id="duration" value="60" required> 
            <button type="submit">Generate Token</button>             
        </form>         
        <h2>Generated Tokens</h2> 
        <table> 
            <thead> 
                <tr> 
                    <th>Token</th> 
                    <th>Expiry</th> 
                    <th>Duration</th> 
                    <th>Used</th> 
                </tr>                 
            </thead>             
            <tbody> 
                @foreach($tokens as $token)
                <tr> 
                    <td>{{ $token->token }}</td> 
                    <td>{{ $token->expiry->format('Y-m-d H:i') }}</td> 
                    <!-- Format the expiry -->                     
                    <td>{{ $token->duration }} min</td> 
                    <td>{{ $token->used ? 'Yes' : 'No' }}</td> 
                </tr>                 
                @endforeach
            </tbody>             
        </table>         
        @if($tokens->isEmpty())
        <p>No tokens have been generated yet.</p> 
        @endif
    </body>     
</html>
